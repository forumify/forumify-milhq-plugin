<?php

declare(strict_types=1);

namespace Forumify\Milhq\Repository;

use Forumify\Core\Repository\AbstractRepository;
use Forumify\Milhq\Entity\CourseClassStudent;

/**
 * @extends AbstractRepository<CourseClassStudent>
 */
class CourseClassStudentRepository extends AbstractRepository
{
    public static function getEntityClass(): string
    {
        return CourseClassStudent::class;
    }
}
