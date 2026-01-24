<?php

declare(strict_types=1);

namespace Forumify\Milhq\Repository;

use Forumify\Core\Repository\AbstractRepository;
use Forumify\Milhq\Entity\Record\CombatRecord;

/**
 * @extends AbstractRepository<CombatRecord>
 */
class CombatRecordRepository extends AbstractRepository
{
    public static function getEntityClass(): string
    {
        return CombatRecord::class;
    }
}
