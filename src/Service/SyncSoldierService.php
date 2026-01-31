<?php

declare(strict_types=1);

namespace Forumify\Milhq\Service;

use Exception;
use Forumify\Core\Entity\User;
use Forumify\Core\Repository\SettingRepository;
use Forumify\Core\Repository\UserRepository;
use Forumify\Milhq\Entity\Soldier;
use Forumify\Milhq\Repository\SoldierRepository;
use Forumify\Plugin\Service\PluginVersionChecker;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\Asset\Packages;
use Symfony\Component\String\Slugger\SluggerInterface;
use Twig\Environment;

class SyncSoldierService
{
    public function __construct(
        private readonly SoldierRepository $soldierRepository,
        private readonly UserRepository $userRepository,
        private readonly Environment $twig,
        private readonly SettingRepository $settingRepository,
        private readonly FilesystemOperator $avatarStorage,
        private readonly FilesystemOperator $milhqAssetStorage,
        private readonly SluggerInterface $slugger,
        private readonly Packages $packages,
        private readonly PluginVersionChecker $pluginVersionChecker,
    ) {
    }

    public function sync(int $userId): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        try {
            $this->doSync($userId);
        } catch (\Exception) {
        }
    }

    private function isEnabled(): bool
    {
        return $this->pluginVersionChecker->isVersionInstalled('forumify/forumify-milhq-plugin', 'premium')
            && ($this->settingRepository->get('milhq.profile.overwrite_display_names')
            || $this->settingRepository->get('milhq.profile.overwrite_signatures')
            || $this->settingRepository->get('milhq.profile.overwrite_avatars'));
    }

    private function doSync(int $userId): void
    {
        $displayNameEnabled = $this->settingRepository->get('milhq.profile.overwrite_display_names');
        $signatureEnabled = $this->settingRepository->get('milhq.profile.overwrite_signatures');
        $avatarEnabled = $this->settingRepository->get('milhq.profile.overwrite_avatars');

        $forumifyUser = $this->userRepository->find($userId);
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
