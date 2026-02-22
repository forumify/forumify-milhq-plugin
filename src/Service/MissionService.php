<?php

declare(strict_types=1);

namespace Forumify\Milhq\Service;

use Forumify\Calendar\Entity\CalendarEvent;
use Forumify\Calendar\Repository\CalendarEventRepository;
use Forumify\Core\Entity\Notification;
use Forumify\Core\Notification\NotificationService;
use Forumify\Core\Repository\ACLRepository;
use Forumify\Core\Repository\SettingRepository;
use Forumify\Core\Repository\UserRepository;
use Forumify\Milhq\Entity\Mission;
use Forumify\Milhq\Entity\Soldier;
use Forumify\Milhq\Notification\MissionCreatedNotificationType;
use Forumify\Milhq\Repository\SoldierRepository;
use Forumify\Milhq\Repository\StatusRepository;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class MissionService
{
    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly ACLRepository $ACLRepository,
        private readonly SettingRepository $settingRepository,
        private readonly UserRepository $userRepository,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly SoldierRepository $soldierRepository,
        private readonly StatusRepository $statusRepository,
        private readonly ?CalendarEventRepository $calendarEventRepository = null,
    ) {
    }

    public function createOrUpdateCalendarEvent(Mission $mission): void
    {
        if ($this->calendarEventRepository === null) {
            // Calendar plugin not installed
            return;
        }

        $calendar = $mission->getCalendar();
        if ($calendar === null) {
            // No events should be created
            return;
        }

        $event = $mission->getCalendarEvent() ?? new CalendarEvent();
        $event->setCalendar($mission->getCalendar());
        $event->setTitle($mission->getTitle());
        $event->setStart($mission->getStart());
        $event->setEnd($mission->getEnd());

        $missionLink = $this->urlGenerator->generate('milhq_missions_view', ['id' => $mission->getId()]);
        $content = "<p><a href='$missionLink' target='_blank'><i class='ph ph-arrow-square-out'></i> View mission</a></p>";
        $event->setContent($content);

        $mission->setCalendarEvent($event);
        $this->calendarEventRepository->save($event);
    }

    public function removeCalendarEvent(Mission $mission): void
    {
        if ($this->calendarEventRepository === null) {
            // Calendar plugin not installed
            return;
        }

        $event = $mission->getCalendarEvent();
        if ($event === null) {
            return;
        }

        $this->calendarEventRepository->remove($event);
    }

    public function sendNotification(Mission $mission): void
    {
        if (!$mission->isSendNotification()) {
            return;
        }

        $recipients = $this->getRecipientsForMission($mission);
        foreach ($recipients as $recipient) {
            $notification = new Notification(MissionCreatedNotificationType::TYPE, $recipient, [
                'mission' => $mission,
            ]);
            $this->notificationService->sendNotification($notification);
        }
    }

    /**
     * @param Mission $mission
     * @return array
     */
    private function getRecipientsForMission(Mission $mission): array
    {
        $usersWithAccess = $this->getForumifyUsersWithMissionAccess($mission);
        if (empty($usersWithAccess)) {
            return [];
        }

        $soldiers = $this->getActiveDutySoldiersByUser($usersWithAccess);

        $recipients = [];
        foreach ($soldiers as $user) {
            $fUser = $user->getUser();
            if ($fUser !== null) {
                $recipients[] = $fUser;
            }
        }

        return $recipients;
    }

    private function getForumifyUsersWithMissionAccess(Mission $mission): array
    {
        $operation = $mission->getOperation();
        $acl = $this->ACLRepository->findOneByEntityAndPermission($operation, 'view_missions');
        if ($acl === null) {
            return [];
        }

        $usersWithAccess = [];
        foreach ($acl->getRoles() as $role) {
            if ($role->getSlug() === 'user') {
                return $this->userRepository->findAll();
            }

            foreach ($role->getUsers() as $user) {
                $usersWithAccess[$user->getId()] = $user;
            }
        }
        return $usersWithAccess;
    }

    /**
     * @return array<Soldier>
     */
    private function getActiveDutySoldiersByUser(array $users): array
    {
        $qb = $this
            ->soldierRepository
            ->createQueryBuilder('s')
            ->where('s.user IN (:users)')
            ->setParameter('users', $users)
        ;

        $enlistmentStatuses = $this->settingRepository->get('milhq.enlistment.status') ?? [];
        if (!empty($enlistmentStatuses)) {
            $statuses = $this->statusRepository->findBy(['id' => $enlistmentStatuses]);
            if (!empty($statuses)) {
                $qb
                    ->andWhere('s.status NOT IN (:statuses)')
                    ->setParameter('statuses', $statuses)
                ;
            }
        }

        return $qb->getQuery()->getResult();
    }
}
