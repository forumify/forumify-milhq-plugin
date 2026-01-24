<?php

declare(strict_types=1);

namespace Forumify\Milhq\Admin\EventSubscriber;

use Forumify\Admin\Crud\Event\PreSaveCrudEvent;
use Forumify\Core\Service\MediaService;
use Forumify\Milhq\Entity\Rank;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class RankCrudSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly MediaService $mediaService,
        private readonly FilesystemOperator $milhqAssetStorage,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [PreSaveCrudEvent::getName(Rank::class) => 'preSaveRank'];
    }

    /**
     * @param PreSaveCrudEvent<Rank> $event
     */
    public function preSaveRank(PreSaveCrudEvent $event): void
    {
        $rank = $event->getEntity();
        $form = $event->getForm();
        $newImage = $form->get('newImage')->getData();
        if (!($newImage instanceof UploadedFile)) {
            return;
        }

        $image = $this->mediaService->saveToFilesystem($this->milhqAssetStorage, $newImage);
        $rank->setImage($image);
        $rank->setImageDirty();
    }
}
