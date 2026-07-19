<?php

declare(strict_types=1);

namespace Forumify\Milhq\Calendar\EventSubscriber;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Forumify\Calendar\Entity\CalendarEvent;
use Forumify\Calendar\Repository\CalendarEventRepository;
use Forumify\Milhq\Entity\CourseClass;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AsEntityListener(Events::postPersist, 'postSave', entity: CourseClass::class)]
#[AsEntityListener(Events::postUpdate, 'postSave', entity: CourseClass::class)]
#[AsEntityListener(Events::postRemove, 'postRemove', entity: CourseClass::class)]
class CourseClassListener
{
    public function __construct(
        private readonly CalendarEventRepository $calendarEventRepository,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function postSave(CourseClass $class): void
    {
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

    public function postRemove(CourseClass $class): void
    {
        $event = $class->getEvent();
        if ($event === null) {
            return;
        }

        $this->calendarEventRepository->remove($event);
    }
}
