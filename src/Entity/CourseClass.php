<?php

declare(strict_types=1);

namespace Forumify\Milhq\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\CreateProvider;
use Doctrine\ORM\Mapping as ORM;
use Forumify\Calendar\Entity\Calendar;
use Forumify\Calendar\Entity\CalendarEvent;
use Forumify\Core\Entity\AuditableEntityInterface;
use Forumify\Milhq\Api\Processor\CourseClassProcessor;
use Forumify\Milhq\Repository\CourseClassRepository;
use Forumify\Plugin\Attribute\PluginVersion;
use Symfony\Component\Serializer\Attribute\Groups;

if (class_exists(\Forumify\Calendar\ForumifyCalendarPlugin::class)) {
    #[PluginVersion('forumify/forumify-milhq-plugin', 'premium')]
    #[ORM\Entity(repositoryClass: CourseClassRepository::class)]
    #[ORM\Table('milhq_course_class')]
    #[ApiResource(
        routePrefix: '/milhq',
        normalizationContext: ['groups' => ['MilhqSummary']],
        order: ['start' => 'DESC'],
        operations: [
            new Get(extraProperties: ['acl' => ['permission' => 'view_classes', 'entity' => 'course']]),
            new GetCollection(extraProperties: ['acl' => ['permission' => 'view_classes', 'entity' => 'course']]),
            new Patch(
                extraProperties: ['acl' => ['permission' => 'manage_classes', 'entity' => 'course']],
                processor: CourseClassProcessor::class,
            ),
            new Delete(extraProperties: ['acl' => ['permission' => 'manage_classes', 'entity' => 'course']]),
            new GetCollection(
                uriTemplate: '/courses/{courseId}/classes',
                uriVariables: [
                    'courseId' => new Link(fromClass: Course::class, toProperty: 'course'),
                ],
                extraProperties: ['acl' => ['permission' => 'view_classes', 'entity' => 'course']],
            ),
            new Post(
                uriTemplate: '/courses/{courseId}/classes',
                uriVariables: [
                    'courseId' => new Link(
                        fromClass: Course::class,
                        toProperty: 'course',
                        security: 'is_granted("MILHQ_COURSE_CLASS_MANAGE", course)',
                    ),
                ],
                provider: CreateProvider::class,
                processor: CourseClassProcessor::class,
            ),
        ],
    )]
    class CourseClass implements AuditableEntityInterface
    {
        use CourseClassFields;

        #[ORM\ManyToOne(targetEntity: Calendar::class)]
        #[ORM\JoinColumn(onDelete: 'SET NULL')]
        #[Groups('CourseClass')]
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

        #[Groups('CourseClass::read')]
        public function getEvent(): ?CalendarEvent
        {
            return $this->calendarEvent;
        }

        public function setEvent(?CalendarEvent $calendarEvent): void
        {
            $this->calendarEvent = $calendarEvent;
        }
    }
} else {
    #[PluginVersion('forumify/forumify-milhq-plugin', 'premium')]
    #[ORM\Entity(repositoryClass: CourseClassRepository::class)]
    #[ORM\Table('milhq_course_class')]
    #[ApiResource(
        routePrefix: '/milhq',
        normalizationContext: ['groups' => ['MilhqSummary']],
        order: ['start' => 'DESC'],
        operations: [
            new Get(extraProperties: ['acl' => ['permission' => 'view_classes', 'entity' => 'course']]),
            new GetCollection(extraProperties: ['acl' => ['permission' => 'view_classes', 'entity' => 'course']]),
            new Patch(
                extraProperties: ['acl' => ['permission' => 'manage_classes', 'entity' => 'course']],
                processor: CourseClassProcessor::class,
            ),
            new Delete(extraProperties: ['acl' => ['permission' => 'manage_classes', 'entity' => 'course']]),
            new GetCollection(
                uriTemplate: '/courses/{courseId}/classes',
                uriVariables: [
                    'courseId' => new Link(fromClass: Course::class, toProperty: 'course'),
                ],
                extraProperties: ['acl' => ['permission' => 'view_classes', 'entity' => 'course']],
            ),
            new Post(
                uriTemplate: '/courses/{courseId}/classes',
                uriVariables: [
                    'courseId' => new Link(
                        fromClass: Course::class,
                        toProperty: 'course',
                        security: 'is_granted("MILHQ_COURSE_CLASS_MANAGE", course)',
                    ),
                ],
                provider: CreateProvider::class,
                processor: CourseClassProcessor::class,
            ),
        ],
    )]
    // phpcs:ignore
    class CourseClass implements AuditableEntityInterface
    {
        use CourseClassFields;
    }
}
