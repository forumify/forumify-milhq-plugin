<?php

declare(strict_types=1);

namespace Forumify\Milhq\Repository;

use Forumify\Core\Repository\AbstractRepository;
use Forumify\Milhq\Entity\Record\RankRecord;
use Forumify\Milhq\Entity\Soldier;

class RankRecordRepository extends AbstractRepository
{
    public static function getEntityClass(): string
    {
        return RankRecord::class;
    }

    public function findLastBySoldier(Soldier $soldier): ?RankRecord
    {
        return $this->createQueryBuilder('rr')
            ->where('rr.soldier = :soldier')
            ->orderBy('rr.createdAt', 'DESC')
            ->setMaxResults(1)
            ->setParameter('soldier', $soldier)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
