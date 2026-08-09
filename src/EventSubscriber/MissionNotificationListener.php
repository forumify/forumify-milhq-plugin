<?php

declare(strict_types=1);

namespace Forumify\Milhq\EventSubscriber;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Forumify\Milhq\Entity\Mission;
use Forumify\Core\Entity\Notification;
use Forumify\Core\Notification\NotificationService;
use Forumify\Core\Repository\ACLRepository;
use Forumify\Core\Repository\SettingRepository;
use Forumify\Core\Repository\UserRepository;
use Forumify\Milhq\Entity\Soldier;
use Forumify\Milhq\Notification\MissionCreatedNotificationType;
use Forumify\Milhq\Repository\SoldierRepository;
use Forumify\Milhq\Repository\StatusRepository;

#[AsEntityListener(Events::postPersist, 'postPersist', entity: Mission::class)]
class MissionNotificationListener
{
    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly ACLRepository $ACLRepository,
        private readonly SettingRepository $settingRepository,
        private readonly UserRepository $userRepository,
        private readonly SoldierRepository $soldierRepository,
        private readonly StatusRepository $statusRepository,
    ) {
    }

    public function postPersist(Mission $mission): void
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
