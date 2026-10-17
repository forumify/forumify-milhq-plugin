<?php

declare(strict_types=1);

namespace Forumify\Milhq\Admin\EventSubscriber;

use Forumify\Admin\Crud\Event\PreSaveCrudEvent;
use Forumify\Milhq\Entity\FormField;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class FormFieldCrudSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [PreSaveCrudEvent::getName(FormField::class) => 'preSaveField'];
    }

    /**
     * @param PreSaveCrudEvent<FormField> $event
     */
    public function preSaveField(PreSaveCrudEvent $event): void
    {
        $event->getEntity()->generateKeyIfEmpty();
    }
}
