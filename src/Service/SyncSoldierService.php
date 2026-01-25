<?php

declare(strict_types=1);

namespace Forumify\Milhq\Service;

use Exception;
use Forumify\Core\Entity\User;
use Forumify\Core\Repository\SettingRepository;
use Forumify\Core\Repository\UserRepository;
use Forumify\Milhq\Entity\Soldier;
use Forumify\Milhq\Message\SyncSoldierMessage;
use Forumify\Milhq\Repository\SoldierRepository;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Twig\Environment;

class SyncSoldierService
{
    public function __construct(
        private readonly SoldierRepository $soldierRepository,
        private readonly UserRepository $userRepository,
        private readonly Environment $twig,
        private readonly SettingRepository $settingRepository,
        private readonly MessageBusInterface $messageBus,
        private readonly FilesystemOperator $avatarStorage,
        private readonly FilesystemOperator $milhqAssetStorage,
        private readonly SluggerInterface $slugger,
        private readonly Packages $packages,
    ) {
    }

    public function sync(int $userId, bool $async = true): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        $message = new SyncSoldierMessage($userId);
        if ($async) {
            $this->messageBus->dispatch($message);
            return;
        }

        try {
            $this->doSync($message);
        } catch (\Exception) {
        }
    }

    private function isEnabled(): bool
    {
        return $this->settingRepository->get('milhq.profile.overwrite_display_names')
            || $this->settingRepository->get('milhq.profile.overwrite_signatures')
            || $this->settingRepository->get('milhq.profile.overwrite_avatars');
    }

    public function doSync(SyncSoldierMessage $message): void
    {
        $displayNameEnabled = $this->settingRepository->get('milhq.profile.overwrite_display_names');
        $signatureEnabled = $this->settingRepository->get('milhq.profile.overwrite_signatures');
        $avatarEnabled = $this->settingRepository->get('milhq.profile.overwrite_avatars');

        $forumifyUser = $this->userRepository->find($message->userId);
        if ($forumifyUser === null) {
            return;
        }

        $soldier = $this->soldierRepository->findOneBy(['user' => $forumifyUser]);
        if ($soldier === null) {
            return;
        }

        if ($displayNameEnabled) {
            $this->syncDisplayName($forumifyUser, $soldier);
        }

        if ($signatureEnabled) {
            $this->syncSignature($forumifyUser, $soldier);
        }

        if ($avatarEnabled) {
            $this->syncAvatar($forumifyUser, $soldier);
        }

        $this->userRepository->save($forumifyUser);
    }

    private function syncDisplayName(User $user, Soldier $soldier): void
    {
        // TODO: take into account during migration user. => soldier.
        $template = $this->settingRepository->get('milhq.profile.display_name_format');
        if ($template === null) {
            $template = '{{soldier.rank.abbreviation}} {{soldier.name}}';
        }

        try {
            $twigTemplate = $this->twig->createTemplate($template);
            $displayName = $twigTemplate->render(['soldier' => $soldier]);
        } catch (\Exception) {
            return;
        }

        $user->setDisplayName($displayName);
    }

    private function syncSignature(User $user, Soldier $soldier): void
    {
        $signature = $soldier->getSignature();
        if (!$signature) {
            return;
        }

        $imgUrl = $this->packages->getUrl($signature, 'milhq.asset');
        $user->setSignature(
            '<p class="ql-align-center"><img src="' . $imgUrl . '" style="max-width: 1000px; width: 100%; max-height: 200px; height: auto"/></p>',
        );
    }

    private function syncAvatar(User $user, Soldier $soldier): void
    {
        $rankImgUrl = $soldier->getRank()?->getImage();
        if ($rankImgUrl === null) {
            return;
        }

        $ext = pathinfo($rankImgUrl, PATHINFO_EXTENSION);
        $filename = $this->slugger
            ->slug($soldier->getRank()->getId() . '-' . $soldier->getRank()->getName())
            ->lower()
            ->append('.', $ext)
            ->toString();

        if ($filename === $user->getAvatar()) {
            return;
        }

        try {
            $rankImg = $this->milhqAssetStorage->read($rankImgUrl);
        } catch (Exception) {
            return;
        }

        try {
            $this->avatarStorage->write($filename, $rankImg);
        } catch (FilesystemException) {
            return;
        }

        $user->setAvatar($filename);
    }
}
