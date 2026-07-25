<?php

declare(strict_types=1);

namespace Forumify\Milhq\Service;

use Forumify\Milhq\Admin\Service\RecordService;
use Forumify\Milhq\Entity\CourseClass;
use Forumify\Milhq\Entity\CourseClassStudent;
use Forumify\Milhq\Entity\Enum\CourseResult;
use Forumify\Milhq\Entity\Record\AwardRecord;
use Forumify\Milhq\Entity\Record\QualificationRecord;
use Forumify\Milhq\Entity\Record\RecordInterface;
use Forumify\Milhq\Entity\Record\ServiceRecord;
use Forumify\Milhq\Exception\MilhqException;
use Forumify\Milhq\Repository\AwardRepository;
use Forumify\Milhq\Repository\AwardTierRepository;
use Forumify\Milhq\Repository\QualificationRepository;
use Forumify\Milhq\Repository\QualificationTierRepository;

class CourseClassService
{
    public function __construct(
        private readonly RecordService $recordService,
        private readonly QualificationRepository $qualificationRepository,
        private readonly QualificationTierRepository $qualificationTierRepository,
        private readonly AwardRepository $awardRepository,
        private readonly AwardTierRepository $awardTierRepository,
    ) {
    }

    /**
     * @throws MilhqException
     */
    public function processResult(CourseClass $class): void
    {
        $records = [];

        $this->addServiceRecords($records, $class);
        $this->addQualificationRecords($records, $class);
        $this->addAwardRecords($records, $class);

        $this->recordService->createRecords($records, true);
    }

    /**
     * @param array<RecordInterface> $records
     */
    private function addServiceRecords(array &$records, CourseClass $class): void
    {
        foreach ($class->getInstructors() as $instructor) {
            $recipient = $instructor->getSoldier();
            if ($recipient === null) {
                continue;
            }

            $text = 'Attended ' . $class->getTitle();
            if ($instructor->getInstructor() !== null) {
                $text .= ' as ' . $instructor->getInstructor()->getTitle();
            }

            $record = new ServiceRecord();
            $record->setSoldier($recipient);
            $record->setText($text);
            $records[] = $record;
        }

        $students = $class->getStudents()->filter(fn (CourseClassStudent $s) => $s->getResult() === CourseResult::Passed);
        /** @var CourseClassStudent $student */
        foreach ($students as $student) {
            $recipient = $student->getSoldier();
            if ($recipient === null) {
                continue;
            }

            $text = $student->getServiceRecordTextOverride() ?: "Graduated {$class->getTitle()}";

            $record = new ServiceRecord();
            $record->setSoldier($recipient);
            $record->setText($text);
            $records[] = $record;
        }
    }

    /**
     * @param array<RecordInterface> $records
     */
    private function addQualificationRecords(array &$records, CourseClass $class): void
    {
        $allowed = $class->getCourse()->getQualifications();
        $qualifications = [];
        $tiers = [];

        $students = $class->getStudents()->filter(fn (CourseClassStudent $s) => $s->getResult() === CourseResult::Passed);
        /** @var CourseClassStudent $student */
        foreach ($students as $student) {
            $recipient = $student->getSoldier();
            if ($recipient === null) {
                continue;
            }

            foreach ($student->getQualifications() as $qualificationId => $tierId) {
                if (!in_array($qualificationId, $allowed, true)) {
                    continue;
                }

                $qualifications[$qualificationId] ??= $this->qualificationRepository->find($qualificationId);
                if ($qualifications[$qualificationId] === null) {
                    continue;
                }

                $tier = null;
                if ($tierId !== null) {
                    $tiers[$tierId] ??= $this->qualificationTierRepository->find($tierId);
                    $tier = $tiers[$tierId];
                }

                $record = new QualificationRecord();
                $record->setQualification($qualifications[$qualificationId]);
                $record->setTier($tier);
                $record->setSoldier($recipient);
                $records[] = $record;
            }
        }
    }

    /**
     * @param array<RecordInterface> $records
     */
    private function addAwardRecords(array &$records, CourseClass $class): void
    {
        $allowed = $class->getCourse()->getAwards();
        $awards = [];
        $tiers = [];

        $students = $class->getStudents()->filter(fn (CourseClassStudent $s) => $s->getResult() === CourseResult::Passed);
        /** @var CourseClassStudent $student */
        foreach ($students as $student) {
            $recipient = $student->getSoldier();
            if ($recipient === null) {
                continue;
            }

            foreach ($student->getAwards() as $awardId => $tierId) {
                if (!in_array($awardId, $allowed, true)) {
                    continue;
                }

                $awards[$awardId] ??= $this->awardRepository->find($awardId);
                $award = $awards[$awardId];
                if ($award === null) {
                    continue;
                }

                $tier = null;
                if ($tierId !== null) {
                    $tiers[$tierId] ??= $this->awardTierRepository->find($tierId);
                    $tier = $tiers[$tierId];
                }

                $record = new AwardRecord();
                $record->setAward($award);
                $record->setTier($tier);
                $record->setSoldier($recipient);
                $records[] = $record;
            }
        }
    }
}
