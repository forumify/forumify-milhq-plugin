<?php

declare(strict_types=1);

namespace Forumify\Milhq\Repository;

use Forumify\Core\Repository\AbstractRepository;
use Forumify\Milhq\Entity\Rank;

/**
 * @extends AbstractRepository<Rank>
 */
class RankRepository extends AbstractRepository
{
    public static function getEntityClass(): string
    {
        return Rank::class;
    }
}
