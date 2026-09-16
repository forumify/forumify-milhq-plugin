<?php

declare(strict_types=1);

namespace Forumify\Milhq\Repository;

use Forumify\Core\Repository\AbstractRepository;
use Forumify\Milhq\Entity\AwardGroup;

/**
 * @extends AbstractRepository<AwardGroup>
 */
class AwardGroupRepository extends AbstractRepository
{
    public static function getEntityClass(): string
    {
        return AwardGroup::class;
    }
}
