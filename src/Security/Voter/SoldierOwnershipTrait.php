<?php

declare(strict_types=1);

namespace Forumify\Milhq\Security\Voter;

use Forumify\Core\Entity\AuthorizableInterface;
use Forumify\Milhq\Entity\Soldier;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

trait SoldierOwnershipTrait
{
    private function isOwnSoldier(?Soldier $soldier, TokenInterface $token): bool
    {
        $owner = $soldier?->getUser();
        $user = $token->getUser();
        if ($owner === null || !$user instanceof AuthorizableInterface) {
            return false;
        }

        return $owner->getId() === $user->getUser()->getId();
    }
}
