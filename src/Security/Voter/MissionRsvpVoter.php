<?php

declare(strict_types=1);

namespace Forumify\Milhq\Security\Voter;

use Forumify\Core\Service\ACLService;
use Forumify\Milhq\Entity\Mission;
use Forumify\Milhq\Entity\MissionRsvp;
use Forumify\Milhq\Security\VoterAttribute;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, Mission|MissionRsvp>
 */
class MissionRsvpVoter extends Voter
{
    use SoldierOwnershipTrait;

    public function __construct(private readonly ACLService $aclService)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return match ($attribute) {
            VoterAttribute::MissionRsvpCreate->value => $subject instanceof Mission,
            VoterAttribute::MissionRsvpDelete->value => $subject instanceof MissionRsvp,
            default => false,
        };
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        if ($subject instanceof Mission) {
            return $this->aclService->can('view_missions', $subject->getOperation());
        }

        return $this->isOwnSoldier($subject->getSoldier(), $token)
            || $this->aclService->can('manage_missions', $subject->getMission()->getOperation());
    }
}
