<?php

declare(strict_types=1);

namespace Forumify\Milhq\Repository;

use DateTimeInterface;
use Forumify\Core\Repository\AbstractRepository;
use Forumify\Milhq\Entity\Record\AssignmentRecord;
use Forumify\Milhq\Entity\Soldier;

/**
 * @extends AbstractRepository<AssignmentRecord>
 */
class AssignmentRecordRepository extends AbstractRepository
{
    public static function getEntityClass(): string
    {
        return AssignmentRecord::class;
    }

    /**
     * @return list<array{statusId: int, createdAt: DateTimeInterface}>
     */
    public function findStatusHistory(Soldier $soldier): array
    {
        /** @var list<array{statusId: int, createdAt: DateTimeInterface}> $history */
        $history = $this->createQueryBuilder('ar')
            ->select('s.id AS statusId', 'ar.createdAt AS createdAt')
            ->join('ar.status', 's')
            ->where('ar.soldier = :soldier')
            ->andWhere('ar.type = :type')
            ->orderBy('ar.createdAt', 'ASC')
            ->addOrderBy('ar.id', 'ASC')
            ->setParameter('soldier', $soldier)
            ->setParameter('type', AssignmentRecord::TYPE_PRIMARY)
            ->getQuery()
            ->getArrayResult()
        ;

        return $history;
    }
}
