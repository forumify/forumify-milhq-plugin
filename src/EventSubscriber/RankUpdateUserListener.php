<?php

declare(strict_types=1);

namespace Forumify\Milhq\EventSubscriber;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Forumify\Milhq\Entity\Record\RankRecord;
use Forumify\Milhq\Repository\RankRecordRepository;

#[AsEntityListener(Events::prePersist, 'prePersist', entity: RankRecord::class)]
#[AsEntityListener(Events::preRemove, 'preRemove', entity: RankRecord::class)]
class RankUpdateUserListener
{
    public function __construct(
        private readonly RankRecordRepository $rankRecordRepository,
    ) {
    }

    public function prePersist(RankRecord $record): void
    {
        $record->getSoldier()->setRank($record->getRank());
    }

    public function preRemove(RankRecord $record): void
    {
        $previousRankRecord = $this->rankRecordRepository
            ->createQueryBuilder('rr')
            ->where('rr != :record')
            ->andWhere('rr.user = :user')
            ->setParameter('record', $record)
            ->setParameter('user', $record->getSoldier())
            ->orderBy('rr.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
        $record->getSoldier()->setRank($previousRankRecord?->getRank());
    }
}
