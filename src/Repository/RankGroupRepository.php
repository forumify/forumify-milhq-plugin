<?php

declare(strict_types=1);

namespace Forumify\Milhq\Repository;

use Forumify\Core\Repository\AbstractRepository;
use Forumify\Milhq\Entity\RankGroup;

/**
 * @extends AbstractRepository<RankGroup>
 */
class RankGroupRepository extends AbstractRepository
{
    public static function getEntityClass(): string
    {
        return RankGroup::class;
    }
}
