<?php

declare(strict_types=1);

namespace Forumify\Milhq\Repository;

use Forumify\Core\Repository\AbstractRepository;
use Forumify\Milhq\Entity\Specialty;

/**
 * @extends AbstractRepository<Specialty>
 */
class SpecialtyRepository extends AbstractRepository
{
    public static function getEntityClass(): string
    {
        return Specialty::class;
    }
}
