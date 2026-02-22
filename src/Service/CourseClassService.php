<?php

declare(strict_types=1);

namespace Forumify\Milhq\Service;

use Doctrine\Common\Collections\Collection;
use Forumify\Calendar\Entity\CalendarEvent;
use Forumify\Calendar\Repository\CalendarEventRepository;
use Forumify\Milhq\Admin\Service\RecordService;
use Forumify\Milhq\Entity\CourseClass;
use Forumify\Milhq\Entity\CourseClassStudent;
use Forumify\Milhq\Entity\Record\QualificationRecord;
use Forumify\Milhq\Entity\Record\RecordInterface;
use Forumify\Milhq\Entity\Record\ServiceRecord;
use Forumify\Milhq\Exception\MilhqException;
use Forumify\Milhq\Repository\QualificationRepository;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CourseClassService
{
    public function __construct(
        private readonly RecordService $recordService,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly QualificationRepository $qualificationRepository,
        private readonly ?CalendarEventRepository $calendarEventRepository = null,
    ) {
    }

    public function createOrUpdateCalendarEvent(CourseClass $class): void
    {
        if ($this->calendarEventRepository === null) {
            // Calendar plugin not installed
            return;
        }

        $calendar = $class->getCalendar();
        if ($calendar === null) {
            // No events should be created
            return;
        }

        $event = $class->getEvent() ?? new CalendarEvent();
        $event->setCalendar($calendar);
        $event->setTitle($class->getTitle());
        $event->setStart($class->getStart());
        $event->setEnd($class->getEnd());

        $classLink = $this->urlGenerator->generate('milhq_course_class_view', ['id' => $class->getId()]);
        $content = "<p><a href='$classLink' target='_blank'><i class='ph ph-arrow-square-out'></i> View class</a></p>";
        $event->setContent($content);

        $class->setEvent($event);
        $this->calendarEventRepository->save($event);
    }

    public function removeCalendarEvent(CourseClass $class): void
    {
        if ($this->calendarEventRepository === null) {
            // Calendar plugin not installed
            return;
        }

        $event = $class->getEvent();
        if ($event === null) {
            return;
        }

        $this->calendarEventRepository->remove($event);
    }

    /**
     * @throws MilhqException
     */
    public function processResult(CourseClass $class): void
    {
        $records = [];

        $this->addServiceRecords($records, $class);
        $this->addQualificationRecords($records, $class->getStudents());

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
}
