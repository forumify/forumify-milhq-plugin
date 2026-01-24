<?php

declare(strict_types=1);

namespace Forumify\Milhq\Repository;

use Forumify\Core\Repository\AbstractRepository;
use Forumify\Milhq\Entity\Soldier;

/**
 * @extends AbstractRepository<Soldier>
 */
class SoldierRepository extends AbstractRepository
{
    public static function getEntityClass(): string
    {
        return Soldier::class;
    }
}
