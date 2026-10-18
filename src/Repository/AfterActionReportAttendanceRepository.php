<?php

declare(strict_types=1);

namespace Forumify\Milhq\Repository;

use Forumify\Core\Repository\AbstractRepository;
use Forumify\Milhq\Entity\AfterActionReport;
use Forumify\Milhq\Entity\AfterActionReportAttendance;

/**
 * @extends AbstractRepository<AfterActionReportAttendance>
 */
class AfterActionReportAttendanceRepository extends AbstractRepository
{
    public static function getEntityClass(): string
    {
        return AfterActionReportAttendance::class;
    }

    /**
     * @param array<AfterActionReport> $afterActionReports
     * @return array<int>
     */
    public function findSoldierIdsWithStateInAll(string $state, array $afterActionReports): array
    {
        if (empty($afterActionReports)) {
            return [];
        }

        $soldierIds = $this->createQueryBuilder('a')
            ->select('IDENTITY(a.soldier)')
            ->where('a.afterActionReport IN (:afterActionReports)')
            ->andWhere('a.state = :state')
            ->groupBy('a.soldier')
            ->having('COUNT(a.id) = :count')
            ->setParameter('afterActionReports', $afterActionReports)
            ->setParameter('state', $state)
            ->setParameter('count', count($afterActionReports))
            ->getQuery()
            ->getSingleColumnResult();

        return array_map('intval', $soldierIds);
    }

    /**
     * @param array<AfterActionReport> $afterActionReports
     * @return list<array{missionId: int, soldierId: int, state: string}>
     */
    public function findStatesByAfterActionReports(array $afterActionReports): array
    {
        if (empty($afterActionReports)) {
            return [];
        }

        /** @var list<array{missionId: int|string, soldierId: int|string, state: string}> $rows */
        $rows = $this->createQueryBuilder('a')
            ->select('IDENTITY(aar.mission) AS missionId', 'IDENTITY(a.soldier) AS soldierId', 'a.state')
            ->join('a.afterActionReport', 'aar')
            ->where('a.afterActionReport IN (:afterActionReports)')
            ->orderBy('a.id')
            ->setParameter('afterActionReports', $afterActionReports)
            ->getQuery()
            ->getArrayResult();

        return array_map(fn (array $row) => [
            'missionId' => (int)$row['missionId'],
            'soldierId' => (int)$row['soldierId'],
            'state' => $row['state'],
        ], $rows);
    }
}
