<?php

declare(strict_types=1);

namespace Forumify\Milhq\Security\Voter;

use Forumify\Core\Service\ACLService;
use Forumify\Milhq\Entity\Mission;
use Forumify\Milhq\Entity\Operation;
use Forumify\Milhq\Security\VoterAttribute;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, Operation|Mission>
 */
class MissionVoter extends Voter
{
    public function __construct(private readonly ACLService $aclService)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === VoterAttribute::MissionManage->value
            && ($subject instanceof Operation || $subject instanceof Mission);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $operation = $subject instanceof Mission ? $subject->getOperation() : $subject;
        return $this->aclService->can('manage_missions', $operation);
    }
}
