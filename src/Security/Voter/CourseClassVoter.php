<?php

declare(strict_types=1);

namespace Forumify\Milhq\Security\Voter;

use Forumify\Core\Service\ACLService;
use Forumify\Milhq\Entity\Course;
use Forumify\Milhq\Entity\CourseClass;
use Forumify\Milhq\Entity\CourseClassInstructor;
use Forumify\Milhq\Entity\CourseClassStudent;
use Forumify\Milhq\Security\VoterAttribute;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, Course|CourseClass|CourseClassStudent|CourseClassInstructor>
 */
class CourseClassVoter extends Voter
{
    public function __construct(private readonly ACLService $aclService)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === VoterAttribute::CourseClassManage->value
            && (
                $subject instanceof Course
                || $subject instanceof CourseClass
                || $subject instanceof CourseClassStudent
                || $subject instanceof CourseClassInstructor
            );
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        return $this->aclService->can('manage_classes', $this->getCourse($subject));
    }

    private function getCourse(Course|CourseClass|CourseClassStudent|CourseClassInstructor $subject): Course
    {
        return match (true) {
            $subject instanceof Course => $subject,
            $subject instanceof CourseClass => $subject->getCourse(),
            default => $subject->getClass()->getCourse(),
        };
    }
}
