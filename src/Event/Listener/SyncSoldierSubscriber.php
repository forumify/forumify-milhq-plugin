<?php

declare(strict_types=1);

namespace Forumify\Milhq\Event\Listener;

use Forumify\Milhq\Entity\Record\AssignmentRecord;
use Forumify\Milhq\Entity\Record\RankRecord;
use Forumify\Milhq\Event\RecordsCreatedEvent;
use Forumify\Milhq\Service\SyncSoldierService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SyncSoldierSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly SyncSoldierService $syncUserService,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            RecordsCreatedEvent::class => 'onRecordsCreated',
        ];
    }

    public function onRecordsCreated(RecordsCreatedEvent $event): void
    {
        foreach ($event->records as $record) {
            if (!$record instanceof AssignmentRecord && !$record instanceof RankRecord) {
                continue;
            }

            $forumUser = $record->getSoldier()->getUser();
            if ($forumUser !== null) {
                $this->syncUserService->sync($forumUser->getId());
            }
        }
    }
}
