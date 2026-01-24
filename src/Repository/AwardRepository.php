<?php

declare(strict_types=1);

namespace Forumify\Milhq\Repository;

use Forumify\Core\Repository\AbstractRepository;
use Forumify\Milhq\Entity\Award;

/**
 * @extends AbstractRepository<Award>
 */
class AwardRepository extends AbstractRepository
{
    public static function getEntityClass(): string
    {
        return Award::class;
    }
}
