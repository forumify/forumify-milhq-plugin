<?php

declare(strict_types=1);

namespace Forumify\Milhq\Repository;

use Forumify\Core\Repository\AbstractRepository;
use Forumify\Milhq\Entity\Award;
use Forumify\Milhq\Entity\Record\AwardRecord;
use Forumify\Milhq\Entity\Soldier;

class AwardRecordRepository extends AbstractRepository
{
    public static function getEntityClass(): string
    {
        return AwardRecord::class;
    }

    public function findLastByAward(Soldier $soldier, Award $award): ?AwardRecord
    {
        return $this->createQueryBuilder('ar')
            ->where('ar.soldier = :soldier')
            ->andWhere('ar.award = :award')
            ->orderBy('ar.createdAt', 'DESC')
            ->addOrderBy('ar.id', 'DESC')
            ->setMaxResults(1)
            ->setParameter('soldier', $soldier)
            ->setParameter('award', $award)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
