<?php

declare(strict_types=1);

namespace Forumify\Milhq\EventSubscriber;

use Forumify\Admin\Crud\Event\PreSaveCrudEvent;
use Forumify\Milhq\Entity\Qualification;
use Forumify\Milhq\Repository\QualificationRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class QualificationGroupSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly QualificationRepository $qualificationRepository,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PreSaveCrudEvent::getName(Qualification::class) => 'onPreSave',
        ];
    }

    public function onPreSave(PreSaveCrudEvent $event): void
    {
        $qualification = $event->getEntity();
        if ($event->isNew() || !$qualification instanceof Qualification) {
            return;
        }

        $submitted = $event->getForm()->get('oldGroupId')->getData();
        $oldGroupId = is_numeric($submitted) ? (int)$submitted : null;
        if ($oldGroupId === $qualification->getGroup()?->getId()) {
            return;
        }

        $qualification->setPosition($this->qualificationRepository->getHighestPosition($qualification) + 1);
    }
}
