<?php

declare(strict_types=1);

namespace Forumify\Milhq\Repository;

use Forumify\Core\Repository\AbstractRepository;
use Forumify\Milhq\Entity\Roster;

/**
 * @extends AbstractRepository<Roster>
 */
class RosterRepository extends AbstractRepository
{
    public static function getEntityClass(): string
    {
        return Roster::class;
    }
}
