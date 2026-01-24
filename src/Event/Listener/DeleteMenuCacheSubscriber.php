<?php

declare(strict_types=1);

namespace Forumify\Milhq\Event\Listener;

use Forumify\Admin\Crud\Event\PostSaveCrudEvent;
use Forumify\Core\Twig\Extension\MenuRuntime;
use Forumify\Milhq\Entity\Soldier;
use Forumify\Milhq\Entity\Record\AssignmentRecord;
use Forumify\Milhq\Event\RecordsCreatedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Cache\CacheInterface;

class DeleteMenuCacheSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly CacheInterface $cache,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            RecordsCreatedEvent::class => 'onRecordsCreated',
            PostSaveCrudEvent::getName(Soldier::class) => 'postSaveUser',
        ];
    }

    public function onRecordsCreated(RecordsCreatedEvent $event): void
    {
        foreach ($event->records as $record) {
            if (!$record instanceof AssignmentRecord) {
                continue;
            }

            $forumUser = $record->getSoldier()->getUser();
            if ($forumUser !== null) {
                $this->cache->delete(MenuRuntime::createMenuCacheKey($forumUser));
            }
        }
    }

    /**
     * @param PostSaveCrudEvent<Soldier> $event
     */
    public function postSaveUser(PostSaveCrudEvent $event): void
    {
        $forumifyUser = $event->getEntity()->getUser();
        if ($forumifyUser === null) {
            return;
        }
        $this->cache->delete(MenuRuntime::createMenuCacheKey($forumifyUser));
    }
}
