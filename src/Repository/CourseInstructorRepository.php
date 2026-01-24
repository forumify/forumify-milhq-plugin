<?php

declare(strict_types=1);

namespace Forumify\Milhq\Repository;

use Forumify\Core\Repository\AbstractRepository;
use Forumify\Milhq\Entity\CourseInstructor;

/**
 * @extends AbstractRepository<CourseInstructor>
 */
class CourseInstructorRepository extends AbstractRepository
{
    public static function getEntityClass(): string
    {
        return CourseInstructor::class;
    }

    /**
     * @param CourseInstructor $entity
     */
    public function getHighestPosition(object $entity): int
    {
        try {
            return $this
                ->createQueryBuilder('e')
                ->select('MAX(e.position)')
                ->where('e.course = :course')
                ->setParameter('course', $entity->getCourse())
                ->getQuery()
                ->getSingleScalarResult() ?? 0
            ;
        } catch (\Exception) {
            return 0;
        }
    }
}
