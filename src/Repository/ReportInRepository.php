<?php

declare(strict_types=1);

namespace Forumify\Milhq\Repository;

use Forumify\Core\Repository\AbstractRepository;
use Forumify\Milhq\Entity\ReportIn;

/**
 * @extends AbstractRepository<ReportIn>
 */
class ReportInRepository extends AbstractRepository
{
    public static function getEntityClass(): string
    {
        return ReportIn::class;
    }
}
