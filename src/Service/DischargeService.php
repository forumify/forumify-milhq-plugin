<?php

declare(strict_types=1);

namespace Forumify\Milhq\Service;

use Forumify\Milhq\Admin\Form\Discharge;
use Forumify\Milhq\Admin\Service\RecordService;
use Forumify\Milhq\Entity\Record\AssignmentRecord;
use Forumify\Milhq\Entity\Record\RankRecord;
use Forumify\Milhq\Entity\Record\ServiceRecord;
use Forumify\Milhq\Repository\AssignmentRecordRepository;
use Forumify\Milhq\Repository\SoldierRepository;

class DischargeService
{
    public function __construct(
        private readonly RecordService $recordService,
        private readonly SoldierRepository $soldierRepository,
        private readonly AssignmentRecordRepository $assignmentRecordRepository,
    ) {
    }

    public function discharge(Discharge $discharge): void
    {
        $serviceRecord = $this->createServiceRecord($discharge);
        $this->recordService->createRecords($serviceRecord, true);

        $records = [];
        if ($assignmentRecord = $this->createAssignmentRecord($discharge)) {
            $records[] = $assignmentRecord;
        }

        if ($rankRecord = $this->createRankRecord($discharge)) {
            $records[] = $rankRecord;
        }

        if (!empty($records)) {
            $this->recordService->createRecords($records, false);
        }

        $this->removeFieldsFromUser($discharge);
    }

    private function createAssignmentRecord(Discharge $discharge): ?AssignmentRecord
    {
        if ($discharge->status === null && $discharge->unit === null && $discharge->position === null) {
            return null;
        }

        $assignmentRecord = new AssignmentRecord();
        $assignmentRecord->setSoldier($discharge->user);
        $assignmentRecord->setStatus($discharge->status);
        $assignmentRecord->setUnit($discharge->unit);
        $assignmentRecord->setPosition($discharge->position);

        return $assignmentRecord;
    }

    private function createServiceRecord(Discharge $discharge): ServiceRecord
    {
        $serviceRecord = new ServiceRecord();
        $serviceRecord->setSoldier($discharge->user);
        if ($discharge->reason) {
            $serviceRecord->setText("$discharge->type: $discharge->reason");
        } else {
            $serviceRecord->setText("$discharge->type");
        }

        return $serviceRecord;
    }

    private function createRankRecord(Discharge $discharge): ?RankRecord
    {
        $oldRank = $discharge->user->getRank();
        $newRank = $discharge->rank;
        if ($newRank === null || $oldRank === null || $oldRank->getId() === $newRank->getId()) {
            return null;
        }

        $rankRecord = new RankRecord();
        $rankRecord->setSoldier($discharge->user);

        if ($discharge->rank->getPosition() < $discharge->user->getRank()->getPosition()) {
            $type = 'promotion';
        } else {
            $type = 'demotion';
        }

        $rankRecord->setType($type);
        $rankRecord->setRank($discharge->rank);

        return $rankRecord;
    }

    private function removeFieldsFromUser(Discharge $discharge): void
    {
        $user = $discharge->user;
        if ($discharge->rank === null) {
            $user->setRank(null);
        }

        if ($discharge->unit === null) {
            $user->setUnit(null);
        }

        if ($discharge->position === null) {
            $user->setPosition(null);
        }

        if ($discharge->status === null) {
            $user->setStatus(null);
        }

        $user->setSpecialty(null);

        foreach ($user->getAssignmentRecords() as $assignment) {
            if ($assignment->getType() === AssignmentRecord::TYPE_SECONDARY) {
                $this->assignmentRecordRepository->remove($assignment, false);
            }
        }

        $this->soldierRepository->save($user);
    }
}
