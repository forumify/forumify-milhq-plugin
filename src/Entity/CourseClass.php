<?php

declare(strict_types=1);

namespace Forumify\Milhq\Entity;

use Doctrine\ORM\Mapping as ORM;
use Forumify\Calendar\Entity\Calendar;
use Forumify\Calendar\Entity\CalendarEvent;
use Forumify\Core\Entity\AuditableEntityInterface;
use Forumify\Milhq\Repository\CourseClassRepository;

if (class_exists(\Forumify\Calendar\ForumifyCalendarPlugin::class)) {
    #[ORM\Entity(repositoryClass: CourseClassRepository::class)]
    #[ORM\Table('milhq_course_class')]
    class CourseClass implements AuditableEntityInterface
    {
        use CourseClassFields;

        #[ORM\ManyToOne(targetEntity: Calendar::class)]
        #[ORM\JoinColumn(onDelete: 'SET NULL')]
        private ?Calendar $calendar = null;

        #[ORM\OneToOne(targetEntity: CalendarEvent::class)]
        #[ORM\JoinColumn(onDelete: 'SET NULL')]
        private ?CalendarEvent $calendarEvent = null;

        public function getCalendar(): ?Calendar
        {
            return $this->calendar;
        }

        public function setCalendar(?Calendar $calendar): void
        {
            $this->calendar = $calendar;
        }

        public function getCalendarEvent(): ?CalendarEvent
        {
            return $this->calendarEvent;
        }

        public function setCalendarEvent(?CalendarEvent $calendarEvent): void
        {
            $this->calendarEvent = $calendarEvent;
        }
    }
} else {
    #[ORM\Entity(repositoryClass: CourseClassRepository::class)]
    #[ORM\Table('milhq_course_class')]
    // phpcs:ignore
    class CourseClass implements AuditableEntityInterface
    {
        use CourseClassFields;
    }
}
