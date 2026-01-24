<?php

declare(strict_types=1);

namespace Forumify\Milhq\Controller;

use DateInterval;
use DateTime;
use Forumify\Milhq\Entity\Soldier;
use Forumify\Milhq\Entity\Record\AssignmentRecord;
use Forumify\Milhq\Repository\AssignmentRecordRepository;
use Forumify\Milhq\Repository\AwardRecordRepository;
use Forumify\Milhq\Repository\SoldierRepository;
use Forumify\Milhq\Repository\RankRecordRepository;
use Forumify\Milhq\Repository\ReportInRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SoldierController extends AbstractController
{
    public function __construct(
        private readonly RankRecordRepository $rankRecordRepository,
        private readonly AwardRecordRepository $awardRecordRepository,
        private readonly AssignmentRecordRepository $assignmentRecordRepository,
        private readonly ReportInRepository $reportInRepository,
        private readonly SoldierRepository $userRepository,
    ) {
    }

    #[Route('soldier/{id}', 'soldier')]
    public function __invoke(Soldier $soldier): Response
    {
        $lastReportInDate = $this
            ->reportInRepository
            ->findOneBy(['soldier' => $soldier])
            ?->getLastReportInDate()
        ;

        return $this->render('@ForumifyMilhqPlugin/frontend/soldier/soldier.html.twig', [
            'awards' => $this->getAwardCounts($soldier),
            'reportInDate' => $lastReportInDate,
            'secondaryAssignments' => $this->getSecondaryUnits($soldier),
            'tig' => $this->getTimeInGrade($soldier),
            'tis' => $this->getTimeInService($soldier),
            'soldier' => $soldier,
            'supervisors' => $this->getSupervisors($soldier),
        ]);
    }

    private function getTimeInGrade(Soldier $soldier): ?DateInterval
    {
        $rankRecords = $this->rankRecordRepository
            ->createQueryBuilder('rr')
            ->select('MAX(rr.createdAt)')
            ->where('rr.user = :user')
            ->setParameter('user', $soldier)
            ->getQuery()
            ->getResult()
        ;

        $lastRankRecord = reset($rankRecords);
        if (!$lastRankRecord) {
            return null;
        }

        $lastDate = reset($lastRankRecord);
        if (!$lastDate) {
            return null;
        }

        return (new DateTime($lastDate))->diff(new DateTime());
    }

    private function getTimeInService(Soldier $user): DateInterval
    {
        return $user->getCreatedAt()->diff(new DateTime());
    }

    private function getAwardCounts(Soldier $soldier): array
    {
        return $this->awardRecordRepository
            ->createQueryBuilder('ar')
            ->join('ar.award', 'a')
            ->select('a.id, COUNT(a.id) AS count, a.name, a.image')
            ->where('ar.soldier = :soldier')
            ->groupBy('a.id')
            ->orderBy('a.position', 'ASC')
            ->setParameter('soldier', $soldier)
            ->getQuery()
            ->getArrayResult()
        ;
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
            $unitId = $record->getUnit()?->getId();
            if ($unitId === null) {
                continue;
            }

            if (!isset($grouped[$unitId])) {
                $grouped[$unitId] = ['name' => $record->getUnit()->getName()];
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

    /**
     * @return array<Soldier>
     */
    private function getSupervisors(Soldier $user): array
    {
        $unit = $user->getUnit();
        if ($unit === null) {
            return [];
        }

        $supervisorPositions = $unit->supervisors->toArray();
        if (empty($supervisorPositions)) {
            return [];
        }

        $supervisors = $this->userRepository->findBy(['position' => $supervisorPositions]);
        if ($user->getPosition() === null) {
            return $supervisors;
        }

        $supervisors = array_filter(
            $supervisors,
            fn (Soldier $supervisor) => $user->getPosition()->getPosition() > $supervisor->getPosition()->getPosition(),
        );
        usort($supervisors, fn (Soldier $a, Soldier $b) => $a->getPosition()->getPosition() <=> $b->getPosition()->getPosition());
        return $supervisors;
    }
}
