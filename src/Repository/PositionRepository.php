<?php

declare(strict_types=1);

namespace Forumify\Milhq\Repository;

use Forumify\Core\Repository\AbstractRepository;
use Forumify\Milhq\Entity\Position;

/**
 * @extends AbstractRepository<Position>
 */
class PositionRepository extends AbstractRepository
{
    public static function getEntityClass(): string
    {
        return Position::class;
    }
}
