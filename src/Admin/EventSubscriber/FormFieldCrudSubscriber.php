<?php

declare(strict_types=1);

namespace Forumify\Milhq\Admin\EventSubscriber;

use Forumify\Admin\Crud\Event\PreSaveCrudEvent;
use Forumify\Milhq\Entity\FormField;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\String\Slugger\AsciiSlugger;

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
        $field = $event->getEntity();
        if (!empty($field->getKey())) {
            return;
        }

        $slugger = new AsciiSlugger();
        $field->setKey($slugger->slug($field->getLabel())->slice(0, 64)->toString());
    }
}
