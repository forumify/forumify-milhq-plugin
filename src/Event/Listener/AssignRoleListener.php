<?php

declare(strict_types=1);

namespace Forumify\Milhq\Event\Listener;

use Forumify\Core\Entity\Role;
use Forumify\Core\Repository\RoleRepository;
use Forumify\Core\Repository\SettingRepository;
use Forumify\Core\Repository\UserRepository;
use Forumify\Milhq\Event\SoldierEnlistedEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
class AssignRoleListener
{
    public function __construct(
        private readonly SettingRepository $settingRepository,
        private readonly RoleRepository $roleRepository,
        private readonly UserRepository $userRepository,
    ) {
    }

    public function __invoke(SoldierEnlistedEvent $event): void
    {
        $roleId = $this->settingRepository->get('milhq.enlistment.role');
        if (!$roleId) {
            return;
        }

        /** @var Role|null $role */
        $role = $this->roleRepository->find($roleId);
        if ($role === null) {
            return;
        }

        $user = $event->soldier->getUser();
        if ($user === null) {
            return;
        }

        foreach ($user->getRoleEntities() as $existingRole) {
            if ($role->getId() === $existingRole->getId()) {
                return;
            }
        }

        $user->addRoleEntity($role);
        $this->userRepository->save($user);
    }
}
