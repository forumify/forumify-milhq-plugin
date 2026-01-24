<?php

declare(strict_types=1);

namespace Forumify\Milhq\Repository;

use Forumify\Core\Repository\AbstractRepository;
use Forumify\Milhq\Entity\Record\AssignmentRecord;

class AssignmentRecordRepository extends AbstractRepository
{
    public static function getEntityClass(): string
    {
        return AssignmentRecord::class;
    }
}
