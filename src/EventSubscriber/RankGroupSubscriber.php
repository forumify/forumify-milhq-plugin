<?php

declare(strict_types=1);

namespace Forumify\Milhq\EventSubscriber;

use Doctrine\ORM\EntityManagerInterface;
use Forumify\Admin\Crud\Event\PreSaveCrudEvent;
use Forumify\Milhq\Entity\Rank;
use Forumify\Milhq\Entity\RankGroup;
use Forumify\Milhq\Repository\RankRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class RankGroupSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly RankRepository $rankRepository,
        private readonly EntityManagerInterface $entityManager,
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

        $originalData = $this->entityManager->getUnitOfWork()->getOriginalEntityData($rank);
        $originalGroup = $originalData['group'] ?? null;
        $originalGroupId = $originalGroup instanceof RankGroup ? $originalGroup->getId() : null;

        if ($originalGroupId === $rank->getGroup()?->getId()) {
            return;
        }

        $rank->setPosition($this->rankRepository->getHighestPosition($rank) + 1);
    }
}
