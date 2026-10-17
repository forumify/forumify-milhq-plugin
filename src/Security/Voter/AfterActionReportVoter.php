<?php

declare(strict_types=1);

namespace Forumify\Milhq\Security\Voter;

use Forumify\Core\Service\ACLService;
use Forumify\Milhq\Entity\AfterActionReport;
use Forumify\Milhq\Entity\Mission;
use Forumify\Milhq\Security\VoterAttribute;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, Mission|AfterActionReport>
 */
class AfterActionReportVoter extends Voter
{
    public function __construct(private readonly ACLService $aclService)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === VoterAttribute::AfterActionReportManage->value
            && ($subject instanceof Mission || $subject instanceof AfterActionReport);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $mission = $subject instanceof AfterActionReport ? $subject->getMission() : $subject;
        return $this->aclService->can('manage_after_action_reports', $mission->getOperation());
    }
}
