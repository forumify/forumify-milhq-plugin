<?php

declare(strict_types=1);

namespace Forumify\Milhq\Repository;

use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use Forumify\Core\Repository\AbstractRepository;
use Forumify\Milhq\Entity\Course;
use Forumify\Milhq\Entity\CourseClass;

/**
 * @extends AbstractRepository<CourseClass>
 */
class CourseClassRepository extends AbstractRepository
{
    public static function getEntityClass(): string
    {
        return CourseClass::class;
    }

    public function findLastClassByCourse(Course $course): ?CourseClass
    {
        try {
            return $this->createQueryBuilder('cc')
                ->where('cc.course = :course')
                ->andWhere('cc.start < CURRENT_TIMESTAMP()')
                ->setParameter('course', $course)
                ->setMaxResults(1)
                ->orderBy('cc.start', 'DESC')
                ->getQuery()
                ->getSingleResult();
        } catch (NoResultException|NonUniqueResultException) {
            return null;
        }
    }

    public function getListQuery(?Course $course): QueryBuilder
    {
        $qb = $this->createQueryBuilder('cc')
            ->orderBy('cc.start', 'DESC');

        if ($course !== null) {
            $qb->andWhere('cc.course = :course')
                ->setParameter('course', $course);

            return $qb;
        }

        $qb->innerJoin('cc.course', 'c');
        $this->addACLToQuery($qb, 'view', Course::class, 'c');

        return $qb;
    }
}
