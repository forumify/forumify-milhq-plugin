<?php

declare(strict_types=1);

namespace Forumify\Milhq\Admin\EventSubscriber;

use Forumify\Admin\Crud\Event\PreSaveCrudEvent;
use Forumify\Core\Service\MediaService;
use Forumify\Milhq\Entity\Operation;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class OperationCrudEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly MediaService $mediaService,
        private readonly FilesystemOperator $milhqAssetStorage,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PreSaveCrudEvent::getName(Operation::class) => 'preSave',
        ];
    }

    public function preSave(PreSaveCrudEvent $event): void
    {
        $operation = $event->getEntity();
        $form = $event->getForm();

        $newImage = $form->get('newImage')->getData();
        if (!($newImage instanceof UploadedFile)) {
            return;
        }

        $image = $this->mediaService->saveToFilesystem($this->milhqAssetStorage, $newImage);
        $operation->setImage($image);
    }
}
