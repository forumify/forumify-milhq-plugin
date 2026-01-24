<?php

declare(strict_types=1);

namespace Forumify\Milhq\Service;

use Forumify\Core\Entity\SortableEntityInterface;
use Forumify\Core\Entity\User;
use Forumify\Core\Repository\SettingRepository;
use Forumify\Milhq\Form\Enlistment;
use Forumify\Milhq\Entity\Soldier;
use Forumify\Milhq\Repository\SoldierRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\PropertyAccess\PropertyAccess;

class SoldierService
{
    private array $userIdToSoldier = [];

    public function __construct(
        private readonly SoldierRepository $soldierRepository,
        private readonly SettingRepository $settingRepository,
        private readonly Security $security,
    ) {
    }

    public function getLoggedInSoldier(): ?Soldier
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return null;
        }

        return $this->getSoldier($user);
    }

    public function getSoldier(User $user): ?Soldier
    {
        $userId = $user->getId();
        if (isset($this->userIdToSoldier[$userId])) {
            return $this->userIdToSoldier[$userId];
        }

        $this->userIdToSoldier[$userId] = $this->soldierRepository->findOneBy(['user' => $user]);
        return $this->userIdToSoldier[$userId];
    }

    public function createUser(Enlistment $enlistment): Soldier
    {
        /** @var User $user */
        $user = $this->security->getUser();
        $soldier = new Soldier();
        $soldier->setUser($user);
        $soldier->setName($user->getDisplayName());

        if (!empty($enlistment->firstName) && !empty($enlistment->lastName)) {
            $name = ucfirst($enlistment->firstName) . ' ' . ucfirst($enlistment->lastName);
            $soldier->setName($name);
        }

        $this->soldierRepository->save($soldier);
        return $soldier;
    }

    public function sortSoldiers(&$users): void
    {
        $sortOrder = $this->settingRepository->get('milhq.roster.user_sort_order');
        $sortOrder = empty($sortOrder)
            ? ['rank', 'position', 'specialty']
            : array_map('trim', explode(',', $sortOrder));

        $propertyAccessor = PropertyAccess::createPropertyAccessorBuilder()
            ->disableExceptionOnInvalidPropertyPath()
            ->getPropertyAccessor();

        usort($users, static function (Soldier $a, Soldier $b) use ($propertyAccessor, $sortOrder): int {
            foreach ($sortOrder as $sortField) {
                $valA = $propertyAccessor->getValue($a, $sortField);
                $valB = $propertyAccessor->getValue($b, $sortField);

                $aIsSortable = $valA instanceof SortableEntityInterface;
                $bIsSortable = $valB instanceof SortableEntityInterface;

                if ($aIsSortable && $bIsSortable) {
                    $valA = $valA->getPosition();
                    $valB = $valB->getPosition();
                    if ($valA === $valB) {
                        continue;
                    }
                    return $valA - $valB;
                }

                $diff = (int)$bIsSortable - (int)$aIsSortable;
                if ($diff !== 0) {
                    return $diff;
                }
            }
            return strcmp($a->getName(), $b->getName());
        });
    }
}
