<?php

declare(strict_types=1);

namespace Forumify\Milhq\EventSubscriber;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Forumify\Milhq\Entity\Mission;
use Forumify\Milhq\Service\MissionService;

#[AsEntityListener(Events::postPersist, 'postPersist', entity: Mission::class)]
#[AsEntityListener(Events::postUpdate, 'postUpdate', entity: Mission::class)]
#[AsEntityListener(Events::postRemove, 'postRemove', entity: Mission::class)]
class MissionListener
{
    public function __construct(private readonly MissionService $missionService)
    {
    }

    public function postPersist(Mission $mission): void
    {
        $this->missionService->createOrUpdateCalendarEvent($mission);
        $this->missionService->sendNotification($mission);
    }

    public function postUpdate(Mission $mission): void
    {
        $this->missionService->createOrUpdateCalendarEvent($mission);
    }

    public function postRemove(Mission $mission): void
    {
        $this->missionService->removeCalendarEvent($mission);
    }
}
