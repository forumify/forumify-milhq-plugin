<?php

declare(strict_types=1);

namespace Forumify\Milhq\Service;

use DateTime;
use Forumify\Core\Entity\Notification;
use Forumify\Core\Entity\User;
use Forumify\Core\Notification\GenericEmailNotificationType;
use Forumify\Core\Notification\NotificationService;
use Forumify\Core\Repository\SettingRepository;
use Forumify\Milhq\Admin\Service\RecordService;
use Forumify\Milhq\Entity\Soldier;
use Forumify\Milhq\Entity\ReportIn;
use Forumify\Milhq\Entity\Status;
use Forumify\Milhq\Repository\SoldierRepository;
use Forumify\Milhq\Repository\ReportInRepository;
use Forumify\Milhq\Repository\StatusRepository;
use Forumify\Plugin\Service\PluginVersionChecker;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ReportInService
{
    private ?Status $failureStatus = null;

    public function __construct(
        private readonly SettingRepository $settingRepository,
        private readonly PluginVersionChecker $pluginVersionChecker,
        private readonly ReportInRepository $reportInRepository,
        private readonly NotificationService $notificationService,
        private readonly RecordService $recordService,
        private readonly SoldierService $soldierService,
        private readonly Packages $packages,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly SoldierRepository $soldierRepository,
        private readonly StatusRepository $statusRepository,
    ) {
    }

    public function runReportInChecks(): void
    {
        $soldiers = $this->getSoldiersToCheck();
        if (empty($soldiers)) {
            return;
        }

        $period = (int)$this->settingRepository->get('milhq.report_in.period');
        $warningPeriod = (int)$this->settingRepository->get('milhq.report_in.warning_period');
        $warningsEnabled = $warningPeriod > 0;

        $now = new DateTime();
        $failures = [];
        foreach ($soldiers as $soldier) {
            /** @var ReportIn|null $lastReportIn */
            $lastReportIn = $this->reportInRepository->findOneBy(['soldier' => $soldier]);
            if ($lastReportIn === null) {
                $reportIn = new ReportIn();
                $reportIn->setSoldier($soldier);
                $reportIn->setLastReportInDate($now);
                $this->reportInRepository->save($reportIn, false);
                continue;
            }

            $diff = (int)$now->diff($lastReportIn->getLastReportInDate())->format("%a");
            if ($diff > $period) {
                $failures[] = $soldier;

                $lastReportIn->setReturnStatus($soldier->getStatus());
                $this->reportInRepository->save($lastReportIn, false);
                continue;
            }

            if (!$warningsEnabled || $diff <= $warningPeriod) {
                continue;
            }

            $user = $soldier->getUser();
            if ($user !== null) {
                $this->sendWarning($user, $period - $diff + 1);
            }
        }
        $this->reportInRepository->flush();

        if (empty($failures)) {
            return;
        }

        $failureStatus = $this->getFailureStatus();
        $this->recordService->createRecord('assignment', [
            'status' => $failureStatus,
            'text' => "Status updated to {$failureStatus->getName()} due to a failure to report in.",
            'type' => 'primary',
            'soldiers' => $failures,
        ]);

        foreach ($failures as $soldier) {
            $user = $soldier->getUser();
            if ($user !== null) {
                $this->sendFailure($user, $period);
            }
        }
    }

    public function isEnabled(): bool
    {
        return $this->pluginVersionChecker->isVersionInstalled('forumify/forumify-milhq-plugin', 'premium')
            && $this->settingRepository->get('milhq.report_in.enabled')
            && (int)$this->settingRepository->get('milhq.report_in.period') > 0
            && $this->settingRepository->get('milhq.report_in.failure_status');
    }

    public function canReportIn(): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        $statusId = $this->soldierService->getLoggedInSoldier()?->getStatus()?->getId();
        if ($statusId === null) {
            return false;
        }

        $enabledStatusIds = $this->settingRepository->get('milhq.report_in.enabled_status');
        $enabledStatusIds[] = (int)$this->settingRepository->get('milhq.report_in.failure_status');

        return in_array($statusId, $enabledStatusIds, true);
    }

    public function reportIn(): bool
    {
        $soldier = $this->soldierService->getLoggedInSoldier();
        if ($soldier === null) {
            return false;
        }

        /** @var ReportIn|null $lastReportIn */
        $lastReportIn = $this->reportInRepository->findOneBy(['soldier' => $soldier]);
        if ($lastReportIn === null) {
            $lastReportIn = new ReportIn();
            $lastReportIn->setSoldier($soldier);
        }
        $lastReportIn->setLastReportInDate(new DateTime());

        $updated = false;
        $returnStatus = $lastReportIn->getReturnStatus();
        if ($returnStatus !== null && $soldier->getStatus()?->getId() !== $returnStatus->getId()) {
            $this->recordService->createRecord('assignment', [
                'status' => $returnStatus,
                'text' => "Status reverted back to original due to reporting in.",
                'type' => 'primary',
                'soldiers' => [$soldier],
            ]);
            $updated = true;
        }
        $lastReportIn->setReturnStatus(null);
        $this->reportInRepository->save($lastReportIn);
        return $updated;
    }

    private function getFailureStatus(): Status
    {
        if ($this->failureStatus !== null) {
            return $this->failureStatus;
        }

        $failureStatusId = (int)$this->settingRepository->get('milhq.report_in.failure_status');
        $this->failureStatus = $this->statusRepository->find($failureStatusId);
        if ($this->failureStatus === null) {
            throw new \RuntimeException('No failure status found.');
        }
        return $this->failureStatus;
    }

    /**
     * @return array<Soldier>
     */
    private function getSoldiersToCheck(): array
    {
        $enabledStatuses = $this->settingRepository->get('milhq.report_in.enabled_status');
        if (empty($enabledStatuses) || !is_array($enabledStatuses)) {
            return [];
        }

        return $this->soldierRepository
            ->createQueryBuilder('pu')
            ->select('pu')
            ->where('pu.status IN (:statusIds)')
            ->setParameter('statusIds', $enabledStatuses)
            ->getQuery()
            ->getResult()
        ;
    }

    private function sendWarning(User $user, int $daysLeft): void
    {
        $status = $this->getFailureStatus()->getName();
        $this->notificationService->sendNotification(new Notification(
            GenericEmailNotificationType::TYPE,
            $user,
            [
                'description' => 'milhq.notification.report_in_warning.description',
                'descriptionParams' => [
                    'days' => $daysLeft,
                    'status' => $status,
                ],
                'emailActionLabel' => 'milhq.notification.report_in_warning.action',
                'image' => $this->packages->getUrl('bundles/forumifymilhqplugin/images/milhq.png'),
                'title' => 'milhq.notification.report_in_warning.title',
                'titleParams' => ['status' => $status],
                'url' => $this->urlGenerator->generate('milhq_operations_center'),
            ],
        ));
    }

    private function sendFailure(User $user, int $period): void
    {
        $status = $this->getFailureStatus()->getName();
        $this->notificationService->sendNotification(new Notification(
            GenericEmailNotificationType::TYPE,
            $user,
            [
                'description' => 'milhq.notification.report_in_failure.description',
                'descriptionParams' => [
                    'days' => $period,
                    'status' => $status,
                ],
                'emailActionLabel' => 'milhq.notification.report_in_failure.action',
                'image' => $this->packages->getUrl('bundles/forumifymilhqplugin/images/milhq.png'),
                'title' => 'milhq.notification.report_in_failure.title',
                'titleParams' => ['status' => $status],
                'url' => $this->urlGenerator->generate('milhq_operations_center'),
            ],
        ));
    }
}
