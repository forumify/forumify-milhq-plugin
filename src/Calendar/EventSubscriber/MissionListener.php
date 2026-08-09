<?php

declare(strict_types=1);

namespace Forumify\Milhq\Calendar\EventSubscriber;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Forumify\Calendar\Entity\CalendarEvent;
use Forumify\Calendar\Repository\CalendarEventRepository;
use Forumify\Milhq\Entity\Mission;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AsEntityListener(Events::postPersist, 'postSave', entity: Mission::class)]
#[AsEntityListener(Events::postUpdate, 'postSave', entity: Mission::class)]
#[AsEntityListener(Events::postRemove, 'postRemove', entity: Mission::class)]
class MissionListener
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly ?CalendarEventRepository $calendarEventRepository = null,
    ) {
    }

    public function postSave(Mission $mission): void
    {
        $calendar = $mission->getCalendar();
        if ($calendar === null) {
            // No events should be created
            return;
        }

        $event = $mission->getCalendarEvent() ?? new CalendarEvent();
        $event->setCalendar($mission->getCalendar());
        $event->setTitle($mission->getTitle());
        $event->setStart($mission->getStart());
        $event->setEnd($mission->getEnd());

        $missionLink = $this->urlGenerator->generate('milhq_missions_view', ['id' => $mission->getId()]);
        $content = "<p><a href='$missionLink' target='_blank'><i class='ph ph-arrow-square-out'></i> View mission</a></p>";
        $event->setContent($content);

        $mission->setCalendarEvent($event);
        $this->calendarEventRepository->save($event);
    }

    public function postRemove(Mission $mission): void
    {
        if ($this->calendarEventRepository === null) {
            // Calendar plugin not installed
            return;
        }

        $event = $mission->getCalendarEvent();
        if ($event === null) {
            return;
        }

        $this->calendarEventRepository->remove($event);
    }
}
