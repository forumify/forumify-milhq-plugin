<?php

declare(strict_types=1);

namespace Forumify\Milhq\Repository;

use Forumify\Core\Repository\AbstractRepository;
use Forumify\Milhq\Entity\AfterActionReport;
use Forumify\Milhq\Entity\Operation;

/**
 * @extends AbstractRepository<AfterActionReport>
 */
class AfterActionReportRepository extends AbstractRepository
{
    public static function getEntityClass(): string
    {
        return AfterActionReport::class;
    }

    /**
     * @param array<int> $units
     * @param array<Operation> $operations
     * @return array<AfterActionReport>
     */
    public function findByMissionStartAndUnit(
        \DateTime $from,
        \DateTime $to,
        array $units,
        array $operations,
    ): array {
        $qb = $this->createQueryBuilder('aar')
            ->join('aar.mission', 'm')
            ->join('aar.unit', 'u')
            ->join('m.operation', 'o')
            ->where('m.start BETWEEN :from AND :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->orderBy('m.start', 'ASC')
            ->addOrderBy('u.position', 'ASC')
        ;

        if (!empty($units)) {
            $qb
                ->andWhere('aar.unit IN (:units)')
                ->setParameter('units', $units)
            ;
        }

        if (!empty($operations)) {
            $qb
                ->andWhere('m.operation IN (:operations)')
                ->setParameter('operations', $operations)
            ;
        }

        return $this
            ->addACLToQuery($qb, 'view', Operation::class, 'o')
            ->getQuery()
            ->getResult();
    }
}
