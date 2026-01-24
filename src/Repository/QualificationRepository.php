<?php

declare(strict_types=1);

namespace Forumify\Milhq\Repository;

use Forumify\Core\Repository\AbstractRepository;
use Forumify\Milhq\Entity\Qualification;

/**
 * @extends AbstractRepository<Qualification>
 */
class QualificationRepository extends AbstractRepository
{
    public static function getEntityClass(): string
    {
        return Qualification::class;
    }
}
