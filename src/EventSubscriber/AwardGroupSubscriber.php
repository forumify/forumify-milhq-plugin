<?php

declare(strict_types=1);

namespace Forumify\Milhq\EventSubscriber;

use Forumify\Admin\Crud\Event\PreSaveCrudEvent;
use Forumify\Milhq\Entity\Award;
use Forumify\Milhq\Repository\AwardRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AwardGroupSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly AwardRepository $awardRepository,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PreSaveCrudEvent::getName(Award::class) => 'onPreSave',
        ];
    }

    public function onPreSave(PreSaveCrudEvent $event): void
    {
        $award = $event->getEntity();
        if ($event->isNew() || !$award instanceof Award) {
            return;
        }

        $submitted = $event->getForm()->get('oldGroupId')->getData();
        $oldGroupId = is_numeric($submitted) ? (int)$submitted : null;
        if ($oldGroupId === $award->getGroup()?->getId()) {
            return;
        }

        $award->setPosition($this->awardRepository->getHighestPosition($award) + 1);
    }
}
