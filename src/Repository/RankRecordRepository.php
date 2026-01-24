<?php

declare(strict_types=1);

namespace Forumify\Milhq\Repository;

use Forumify\Core\Repository\AbstractRepository;
use Forumify\Milhq\Entity\Record\RankRecord;

class RankRecordRepository extends AbstractRepository
{
    public static function getEntityClass(): string
    {
        return RankRecord::class;
    }
}
