<?php

declare(strict_types=1);

namespace Forumify\Milhq\Security\Voter;

use Forumify\Core\Service\ACLService;
use Forumify\Milhq\Entity\Form;
use Forumify\Milhq\Entity\FormSubmission;
use Forumify\Milhq\Security\VoterAttribute;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, Form|FormSubmission>
 */
class FormSubmissionVoter extends Voter
{
    use SoldierOwnershipTrait;

    public function __construct(private readonly ACLService $aclService)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return match ($attribute) {
            VoterAttribute::FormSubmissionCreate->value => $subject instanceof Form,
            VoterAttribute::FormSubmissionView->value => $subject instanceof FormSubmission,
            default => false,
        };
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        if ($subject instanceof Form) {
            return $this->aclService->can('create_submissions', $subject);
        }

        return $this->isOwnSoldier($subject->getSoldier(), $token)
            || $this->aclService->can('view_submissions', $subject->getForm());
    }
}
