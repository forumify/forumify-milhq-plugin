<?php

declare(strict_types=1);

namespace Forumify\Milhq\EventSubscriber;

use Forumify\Admin\Crud\Event\PreSaveCrudEvent;
use Forumify\Milhq\Entity\Rank;
use Forumify\Milhq\Repository\RankRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class RankGroupSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly RankRepository $rankRepository,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PreSaveCrudEvent::getName(Rank::class) => 'onPreSave',
        ];
    }

    public function onPreSave(PreSaveCrudEvent $event): void
    {
        $rank = $event->getEntity();
        if ($event->isNew() || !$rank instanceof Rank) {
            return;
        }

        $submitted = $event->getForm()->get('oldGroupId')->getData();
        $oldGroupId = is_numeric($submitted) ? (int)$submitted : null;
        if ($oldGroupId === $rank->getGroup()?->getId()) {
            return;
        }

        $rank->setPosition($this->rankRepository->getHighestPosition($rank) + 1);
    }
}
