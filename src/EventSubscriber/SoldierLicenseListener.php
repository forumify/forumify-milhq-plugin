<?php

declare(strict_types=1);

namespace Forumify\Milhq\EventSubscriber;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Forumify\Milhq\Entity\Soldier;
use Forumify\Milhq\Repository\SoldierRepository;
use Forumify\Plugin\Service\PluginVersionChecker;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

#[AsEntityListener(Events::prePersist, 'checkLicense', entity: Soldier::class)]
class SoldierLicenseListener
{
    public function __construct(
        private readonly PluginVersionChecker $pluginVersionChecker,
        private readonly SoldierRepository $soldierRepository,
    ) {
    }

    public function checkLicense(): void
    {
        if ($this->pluginVersionChecker->isVersionInstalled('forumify/forumify-milhq-plugin', ['basic', 'premium'])) {
            return;
        }

        $count = $this->soldierRepository->count([]);
        if ($count >= 5) {
            throw new AccessDeniedException('You can only have up to 5 soldiers in the free version of MILHQ.');
        }
    }
}
