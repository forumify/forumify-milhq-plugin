<?php

declare(strict_types=1);

namespace Forumify\Milhq\Repository;

use Forumify\Core\Repository\AbstractRepository;
use Forumify\Milhq\Entity\QualificationGroup;

/**
 * @extends AbstractRepository<QualificationGroup>
 */
class QualificationGroupRepository extends AbstractRepository
{
    public static function getEntityClass(): string
    {
        return QualificationGroup::class;
    }
}
