<?php

declare(strict_types=1);

namespace Forumify\Milhq\Repository;

use Forumify\Core\Repository\AbstractRepository;
use Forumify\Milhq\Entity\QualificationTier;

/**
 * @extends AbstractRepository<QualificationTier>
 */
class QualificationTierRepository extends AbstractRepository
{
    public static function getEntityClass(): string
    {
        return QualificationTier::class;
    }
}
