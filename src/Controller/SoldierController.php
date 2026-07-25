<?php

declare(strict_types=1);

namespace Forumify\Milhq\Controller;

use Forumify\Milhq\Entity\Soldier;
use Forumify\Milhq\Entity\Record\AssignmentRecord;
use Forumify\Milhq\Repository\AssignmentRecordRepository;
use Forumify\Milhq\Repository\AwardRecordRepository;
use Forumify\Milhq\Service\SoldierService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SoldierController extends AbstractController
{
    public function __construct(
        private readonly AwardRecordRepository $awardRecordRepository,
        private readonly AssignmentRecordRepository $assignmentRecordRepository,
        private readonly SoldierService $soldierService,
    ) {
    }

    #[Route('soldier/{id}', 'soldier')]
    public function __invoke(Soldier $soldier): Response
    {

        return $this->render('@ForumifyMilhqPlugin/frontend/soldier/soldier.html.twig', [
            'soldier' => $soldier,
            'awards' => $this->getAwardCounts($soldier),
            'reportInDate' => $this->soldierService->getLastReportInDate($soldier),
            'secondaryAssignments' => $this->getSecondaryUnits($soldier),
            'tig' => $this->soldierService->getTimeInGrade($soldier),
            'tis' => $this->soldierService->getTimeInService($soldier),
            'supervisors' => $this->soldierService->getSupervisors($soldier),
            'equipment' => $this->soldierService->getEquipment($soldier),
        ]);
    }

    private function getAwardCounts(Soldier $soldier): array
    {
        $awards = $this->awardRecordRepository
            ->createQueryBuilder('ar')
            ->join('ar.award', 'a')
            ->select('a.id, COUNT(a.id) AS count, a.name, a.image, a.autoAdvanceTiers')
            ->where('ar.soldier = :soldier')
            ->groupBy('a.id')
            ->orderBy('a.position', 'ASC')
            ->setParameter('soldier', $soldier)
            ->getQuery()
            ->getArrayResult()
        ;

        $autoAdvanceAwardIds = array_column(
            array_filter($awards, static fn (array $award) => $award['autoAdvanceTiers']),
            'id',
        );
        $highestTiers = $this->getHighestTiers($soldier, $autoAdvanceAwardIds);

        foreach ($awards as &$award) {
            $tier = $highestTiers[$award['id']] ?? null;
            $award['image'] = $tier['image'] ?? $award['image'];
            $award['tierName'] = $tier['name'] ?? null;
        }

        return $awards;
    }

    /**
     * @param array<int> $awardIds
     * @return array<int, array{name: string, image: ?string}>
     */
    private function getHighestTiers(Soldier $soldier, array $awardIds): array
    {
        if (empty($awardIds)) {
            return [];
        }

        $rows = $this->awardRecordRepository
            ->createQueryBuilder('ar')
            ->join('ar.tier', 't')
            ->select('IDENTITY(ar.award) AS awardId, t.name, t.image, t.position')
            ->where('ar.soldier = :soldier')
            ->andWhere('ar.award IN (:awardIds)')
            ->setParameter('soldier', $soldier)
            ->setParameter('awardIds', $awardIds)
            ->getQuery()
            ->getArrayResult()
        ;

        $highest = [];
        foreach ($rows as $row) {
            $awardId = $row['awardId'];
            if (!isset($highest[$awardId]) || $row['position'] < $highest[$awardId]['position']) {
                $highest[$awardId] = $row;
            }
        }

        return $highest;
    }

    private function getSecondaryUnits(Soldier $soldier): array
    {
        /** @var array<AssignmentRecord> */
        $records = $this->assignmentRecordRepository->findBy([
            'type' => 'secondary',
            'soldier' => $soldier,
        ]);

        $grouped = [];
        foreach ($records as $record) {
            $unit = $record->getUnit();
            if ($unit === null) {
                continue;
            }

            $unitId = $unit->getId();
            if (!isset($grouped[$unitId])) {
                $grouped[$unitId] = ['name' => $unit->getName()];
            }

            $data = [
                $record->getPosition()?->getName(),
                $record->getSpecialty()?->getName(),
                $record->getStatus()?->getName(),
            ];

            $grouped[$unitId]['records'][] = implode(' | ', array_filter($data));
        }

        return $grouped;
    }
}
