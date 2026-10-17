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
use Forumify\Milhq\Repository\MissionRepository;
use Forumify\Plugin\Attribute\PluginVersion;
use Symfony\Component\Serializer\Attribute\Groups;

if (class_exists(\Forumify\Calendar\ForumifyCalendarPlugin::class)) {
    #[PluginVersion('forumify/forumify-milhq-plugin', 'premium')]
    #[ORM\Entity(repositoryClass: MissionRepository::class)]
    #[ORM\Table('milhq_mission')]
    #[ApiResource(
        routePrefix: '/milhq',
        normalizationContext: ['groups' => ['MilhqSummary']],
        order: ['start' => 'DESC'],
        operations: [
            new Get(extraProperties: ['acl' => ['permission' => 'view_missions', 'entity' => 'operation']]),
            new GetCollection(extraProperties: ['acl' => ['permission' => 'view_missions', 'entity' => 'operation']]),
            new Patch(extraProperties: ['acl' => ['permission' => 'manage_missions', 'entity' => 'operation']]),
            new Delete(extraProperties: ['acl' => ['permission' => 'manage_missions', 'entity' => 'operation']]),
            new GetCollection(
                uriTemplate: '/operations/{operationId}/missions',
                uriVariables: [
                    'operationId' => new Link(fromClass: Operation::class, toProperty: 'operation'),
                ],
                extraProperties: ['acl' => ['permission' => 'view_missions', 'entity' => 'operation']],
            ),
            new Post(
                uriTemplate: '/operations/{operationId}/missions',
                uriVariables: [
                    'operationId' => new Link(
                        fromClass: Operation::class,
                        toProperty: 'operation',
                        security: 'is_granted("MILHQ_MISSION_MANAGE", operation)',
                    ),
                ],
                provider: CreateProvider::class,
            ),
        ],
    )]
    class Mission implements AuditableEntityInterface
    {
        use MissionFields;

        #[ORM\ManyToOne(targetEntity: Calendar::class)]
        #[ORM\JoinColumn(onDelete: 'SET NULL')]
        #[Groups('Mission')]
        private ?Calendar $calendar = null;

        #[ORM\OneToOne(targetEntity: CalendarEvent::class)]
        #[ORM\JoinColumn(onDelete: 'SET NULL')]
        #[Groups('Mission::read')]
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
    #[PluginVersion('forumify/forumify-milhq-plugin', 'premium')]
    #[ORM\Entity(repositoryClass: MissionRepository::class)]
    #[ORM\Table('milhq_mission')]
    #[ApiResource(
        routePrefix: '/milhq',
        normalizationContext: ['groups' => ['MilhqSummary']],
        order: ['start' => 'DESC'],
        operations: [
            new Get(extraProperties: ['acl' => ['permission' => 'view_missions', 'entity' => 'operation']]),
            new GetCollection(extraProperties: ['acl' => ['permission' => 'view_missions', 'entity' => 'operation']]),
            new Patch(extraProperties: ['acl' => ['permission' => 'manage_missions', 'entity' => 'operation']]),
            new Delete(extraProperties: ['acl' => ['permission' => 'manage_missions', 'entity' => 'operation']]),
            new GetCollection(
                uriTemplate: '/operations/{operationId}/missions',
                uriVariables: [
                    'operationId' => new Link(fromClass: Operation::class, toProperty: 'operation'),
                ],
                extraProperties: ['acl' => ['permission' => 'view_missions', 'entity' => 'operation']],
            ),
            new Post(
                uriTemplate: '/operations/{operationId}/missions',
                uriVariables: [
                    'operationId' => new Link(
                        fromClass: Operation::class,
                        toProperty: 'operation',
                        security: 'is_granted("MILHQ_MISSION_MANAGE", operation)',
                    ),
                ],
                provider: CreateProvider::class,
            ),
        ],
    )]
    // phpcs:ignore
    class Mission implements AuditableEntityInterface
    {
        use MissionFields;
    }
}
