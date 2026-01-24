<?php

declare(strict_types=1);

namespace Forumify\Milhq\Twig;

use Doctrine\Common\Collections\Collection;
use Forumify\Milhq\Entity\Course;
use Forumify\Milhq\Entity\CourseClassInstructor;
use Forumify\Milhq\Entity\CourseClassStudent;
use Forumify\Milhq\Entity\Soldier;
use Forumify\Milhq\Entity\Qualification;
use Forumify\Milhq\Repository\QualificationRepository;
use Forumify\Milhq\Service\SoldierService;
use Twig\Extension\RuntimeExtensionInterface;

class CourseExtensionRuntime implements RuntimeExtensionInterface
{
    public function __construct(
        private readonly SoldierService $userService,
        private readonly QualificationRepository $qualificationRepository,
    ) {
    }

    /**
     * @param array<int> $ids
     * @return array<string>
     */
    public function getQualifications(array $ids): array
    {
        $qualifications = $this->qualificationRepository->findBy(['id' => $ids]);
        return array_map((fn (Qualification $qual) => $qual->getName()), $qualifications);
    }

    public function getPrerequisites(Course $course): array
    {
        $prerequisites = [];

        $rank = $course->getMinimumRank();
        if ($rank !== null) {
            $prerequisites[] = $rank->getName();
        }

        $qualifications = $this->getQualifications($course->getPrerequisites());
        foreach ($qualifications as $qualification) {
            $prerequisites[] = $qualification;
        }

        return $prerequisites;
    }

    /**
     * @param Collection<int, CourseClassStudent|CourseClassInstructor> $soldiersInClass
     * @return array<int, array{ soldier: Soldier, courseUser: CourseClassStudent|CourseClassInstructor }>
     */
    public function getUsers(Collection $soldiersInClass): array
    {
        if ($soldiersInClass->isEmpty()) {
            return [];
        }

        $soldierIds = $soldiersInClass
            ->map(fn (CourseClassInstructor|CourseClassStudent $user) => $user->getSoldier()->getId())
            ->toArray()
        ;
        $courseUsers = array_combine($soldierIds, $soldiersInClass->toArray());
        $soldiers = array_combine($soldierIds, $soldiersInClass->map((fn ($user) => $user->getUser()))->toArray());

        $this->userService->sortSoldiers($soldiers);

        $return = [];
        foreach ($soldiers as $soldier) {
            $return[$soldier->getId()] = [
                'courseUser' => $courseUsers[$soldier->getId()],
                'soldier' => $soldier,
            ];
        }
        return $return;
    }
}
