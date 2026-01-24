<?php

declare(strict_types=1);

namespace Forumify\Milhq\Admin\EventSubscriber;

use Forumify\Admin\Crud\Event\PreSaveCrudEvent;
use Forumify\Core\Service\MediaService;
use Forumify\Milhq\Entity\Qualification;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class QualificationCrudSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly MediaService $mediaService,
        private readonly FilesystemOperator $milhqAssetStorage,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [PreSaveCrudEvent::getName(Qualification::class) => 'preSaveQualification'];
    }

    /**
     * @param PreSaveCrudEvent<Qualification> $event
     */
    public function preSaveQualification(PreSaveCrudEvent $event): void
    {
        $qualification = $event->getEntity();
        $form = $event->getForm();
        $newImage = $form->get('newImage')->getData();
        if (!($newImage instanceof UploadedFile)) {
            return;
        }

        $image = $this->mediaService->saveToFilesystem($this->milhqAssetStorage, $newImage);
        $qualification->setImage($image);
    }
}
