<?php

declare(strict_types=1);

namespace Forumify\Milhq\Service;

use Forumify\Core\Entity\Notification;
use Forumify\Core\Notification\GenericEmailNotificationType;
use Forumify\Core\Notification\GenericNotificationType;
use Forumify\Core\Notification\NotificationService;
use Forumify\Core\Repository\SettingRepository;
use Forumify\Milhq\Admin\Service\RecordService;
use Forumify\Milhq\Entity\AfterActionReport;
use Forumify\Milhq\Entity\Mission;
use Forumify\Milhq\Entity\Soldier;
use Forumify\Milhq\Entity\Status;
use Forumify\Milhq\Repository\AfterActionReportAttendanceRepository;
use Forumify\Milhq\Repository\AfterActionReportRepository;
use Forumify\Milhq\Repository\CombatRecordRepository;
use Forumify\Milhq\Repository\StatusRepository;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AfterActionReportService
{
    public function __construct(
        private readonly SettingRepository $settingRepository,
        private readonly AfterActionReportRepository $afterActionReportRepository,
        private readonly RecordService $recordService,
        private readonly NotificationService $notificationService,
        private readonly Packages $packages,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly StatusRepository $statusRepository,
        private readonly AfterActionReportAttendanceRepository $attendanceRepository,
        private readonly CombatRecordRepository $combatRecordRepository,
    ) {
    }

    public function save(AfterActionReport $aar, bool $isNew): void
    {
        $this->afterActionReportRepository->save($aar);

        if ($aar->getMission()->isCreateCombatRecords()) {
            $this->syncCombatRecords($aar);
        }

        if ($isNew) {
            $this->handleAbsence($aar);
        }
    }

    public function getAttendanceStates(): array
    {
        $attendanceStates = $this->settingRepository->get('milhq.operations.attendance_states') ?? [];
        if (!is_array($attendanceStates)) {
            $attendanceStates = explode(',', $attendanceStates);
        }
        $attendanceStates = array_map('trim', array_filter($attendanceStates));

        return empty($attendanceStates)
            ? ['present', 'excused', 'absent']
            : $attendanceStates;
    }

    private function syncCombatRecords(AfterActionReport $aar): void
    {
        $present = [];
        foreach ($aar->getSoldiersWithState('present') as $soldier) {
            $present[$soldier->getId()] = $soldier;
        }

        $staleRecords = [];
        foreach ($this->combatRecordRepository->findBy(['afterActionReport' => $aar]) as $record) {
            $soldierId = $record->getSoldier()->getId();
            if (isset($present[$soldierId])) {
                unset($present[$soldierId]);
            } else {
                $staleRecords[] = $record;
            }
        }

        $this->combatRecordRepository->removeAll($staleRecords);
        $this->recordService->createRecord('combat', [
            'afterActionReport' => $aar,
            'sendNotification' => true,
            'text' => $aar->getMission()->getCombatRecordText() ?: $this->getDefaultCombatRecordText($aar->getMission()),
            'soldiers' => array_values($present),
        ]);
    }

    private function getDefaultCombatRecordText(Mission $mission): string
    {
        return "Operation {$mission->getOperation()->getTitle()}: Mission {$mission->getTitle()}";
    }

    private function handleAbsence(AfterActionReport $aar): void
    {
        $absentees = $aar->getSoldiersWithState('absent');
        if (empty($absentees)) {
            return;
        }

        $this->handleAbsenceNotification($absentees, $aar);
        $this->handleConsecutiveAbsences($absentees, $aar);
    }

    /**
     * @param array<Soldier> $absentSoldiers
     */
    private function handleAbsenceNotification(array $absentSoldiers, AfterActionReport $aar)
    {
        $s = $this->settingRepository->get(...);
        $notificationEnabled = (bool)$s('milhq.operations.absent_notification');
        if (!$notificationEnabled) {
            return;
        }

        $notificationMessage = $s('milhq.operations.absent_notification_message');
        $notificationMessage = empty($notificationMessage)
            ? "You have been marked absent from mission {$aar->getMission()->getTitle()}"
            : $notificationMessage;

        foreach ($absentSoldiers as $soldier) {
            $user = $soldier->getUser();
            if ($user === null) {
                continue;
            }

            $this->notificationService->sendNotification(new Notification(
                GenericNotificationType::TYPE,
                $user,
                [
                    'description' => $notificationMessage,
                    'image' => $this->packages->getUrl('bundles/forumifymilhqplugin/images/milhq.png'),
                    'title' => 'Mission absence',
                    'url' => $this->urlGenerator->generate('milhq_aar_view', ['id' => $aar->getId()]),
                ],
            ));
        }
    }

    /**
     * @param array<Soldier> $absentSoldiers
     */
    private function handleConsecutiveAbsences(array $absentSoldiers, AfterActionReport $aar): void
    {
        $s = $this->settingRepository->get(...);
        $consecutiveEnabled = (bool)$s('milhq.operations.consecutive_absent_notification');
        if (!$consecutiveEnabled) {
            return;
        }

        $consecutiveCount = (int)$s('milhq.operations.consecutive_absent_notification_count');
        if ($consecutiveCount < 1) {
            return;
        }

        /** @var array<AfterActionReport> $pastAars */
        $pastAars = $this->afterActionReportRepository
            ->createQueryBuilder('aar')
            ->join('aar.mission', 'm')
            ->where('aar.unit = :unit')
            ->orderBy('m.start', 'DESC')
            ->setMaxResults($consecutiveCount)
            ->setParameter('unit', $aar->getUnit())
            ->getQuery()
            ->getResult()
        ;

        if (count($pastAars) < $consecutiveCount) {
            // not enough AARs to check
            return;
        }

        $consecutiveAbsentIds = $this->attendanceRepository->findSoldierIdsWithStateInAll('absent', $pastAars);
        $consecutiveAbsentees = array_values(array_filter(
            $absentSoldiers,
            fn (Soldier $soldier) => in_array($soldier->getId(), $consecutiveAbsentIds, true),
        ));
        if (empty($consecutiveAbsentees)) {
            return;
        }

        $description = "You have been marked absent $consecutiveCount times in a row. Please contact your leadership immediately or risk punitive action!";
        $consecutiveMessage = $s('milhq.operations.consecutive_absent_notification_message');
        $consecutiveMessage = empty(strip_tags($consecutiveMessage)) ? $description : $consecutiveMessage;

        foreach ($consecutiveAbsentees as $soldier) {
            $user = $soldier->getUser();
            if ($user === null) {
                continue;
            }

            $this->notificationService->sendNotification(new Notification(
                GenericEmailNotificationType::TYPE,
                $user,
                [
                    'description' => $description,
                    'emailActionLabel' => 'View After Action Report',
                    'emailContent' => $consecutiveMessage,
                    'emailTemplate' => '@ForumifyMilhqPlugin/emails/notifications/consecutive_absence.html.twig',
                    'image' => $this->packages->getUrl('bundles/forumifymilhqplugin/images/milhq.png'),
                    'title' => "You have been marked absent $consecutiveCount times consecutively!",
                    'url' => $this->urlGenerator->generate('milhq_aar_view', ['id' => $aar->getId()]),
                ]
            ));
        }

        $consecutiveStatusId = (int)$s('milhq.operations.consecutive_absent_status');
        if ($consecutiveStatusId < 1) {
            return;
        }

        /** @var Status|null $consecutiveStatus */
        $consecutiveStatus = $this->statusRepository->find($consecutiveStatusId);
        if ($consecutiveStatus === null) {
            return;
        }

        $this->recordService->createRecord('assignment', [
            'sendNotification' => true,
            'status' => $consecutiveStatus,
            'text' => "Status updated to {$consecutiveStatus->getName()} due to consecutive absences.",
            'type' => 'primary',
            'soldiers' => $consecutiveAbsentees,
        ]);
    }
}
