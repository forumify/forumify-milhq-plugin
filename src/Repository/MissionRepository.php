<?php

declare(strict_types=1);

namespace Forumify\Milhq\Repository;

use Forumify\Core\Repository\AbstractRepository;
use Forumify\Milhq\Entity\Mission;

/**
 * @extends AbstractRepository<Mission>
 */
class MissionRepository extends AbstractRepository
{
    public static function getEntityClass(): string
    {
        return Mission::class;
    }
}
