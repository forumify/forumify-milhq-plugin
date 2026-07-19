<?php

declare(strict_types=1);

namespace Forumify\Milhq\Service;

use Doctrine\Common\Collections\Collection;
use Forumify\Milhq\Admin\Service\RecordService;
use Forumify\Milhq\Entity\CourseClass;
use Forumify\Milhq\Entity\CourseClassStudent;
use Forumify\Milhq\Entity\Record\AwardRecord;
use Forumify\Milhq\Entity\Record\QualificationRecord;
use Forumify\Milhq\Entity\Record\RecordInterface;
use Forumify\Milhq\Entity\Record\ServiceRecord;
use Forumify\Milhq\Exception\MilhqException;
use Forumify\Milhq\Repository\AwardRepository;
use Forumify\Milhq\Repository\QualificationRepository;

class CourseClassService
{
    public function __construct(
        private readonly RecordService $recordService,
        private readonly QualificationRepository $qualificationRepository,
        private readonly AwardRepository $awardRepository,
    ) {
    }

    /**
     * @throws MilhqException
     */
    public function processResult(CourseClass $class): void
    {
        $records = [];

        $this->addServiceRecords($records, $class);
        $this->addQualificationRecords($records, $class->getStudents());
        $this->addAwardRecords($records, $class->getStudents());

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

        $students = $class->getStudents()->filter(fn (CourseClassStudent $s) => $s->getResult() === 'passed');
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
     * @param Collection<int, CourseClassStudent> $students
     */
    private function addQualificationRecords(array &$records, Collection $students): void
    {
        $qualifications = [];

        foreach ($students as $student) {
            $recipient = $student->getSoldier();
            if ($recipient === null) {
                continue;
            }

            foreach ($student->getQualifications() as $qualificationId) {
                $qualifications[$qualificationId] = isset($qualifications[$qualificationId])
                    ? $qualifications[$qualificationId]
                    : $this->qualificationRepository->find($qualificationId);

                if ($qualifications[$qualificationId] === null) {
                    continue;
                }

                $record = new QualificationRecord();
                $record->setQualification($qualifications[$qualificationId]);
                $record->setSoldier($recipient);
                $records[] = $record;
            }
        }
    }

    /**
     * @param array<RecordInterface> $records
     * @param Collection<int, CourseClassStudent> $students
     */
    private function addAwardRecords(array &$records, Collection $students): void
    {
        $awards = [];

        foreach ($students as $student) {
            $recipient = $student->getSoldier();
            if ($recipient === null) {
                continue;
            }

            foreach ($student->getAwards() as $awardId) {
                $awards[$awardId] = isset($awards[$awardId])
                    ? $awards[$awardId]
                    : $this->awardRepository->find($awardId);

                if ($awards[$awardId] === null) {
                    continue;
                }

                $record = new AwardRecord();
                $record->setAward($awards[$awardId]);
                $record->setSoldier($recipient);
                $records[] = $record;
            }
        }
    }
}
