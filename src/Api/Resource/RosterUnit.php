<?php

declare(strict_types=1);

namespace Forumify\Milhq\Api\Resource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use Forumify\Milhq\Api\Provider\RosterUnitProvider;
use Forumify\Milhq\Entity\Position;
use Forumify\Milhq\Entity\Soldier;
use Forumify\Milhq\Entity\Unit;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    routePrefix: '/milhq',
    normalizationContext: ['groups' => ['MilhqSummary']],
    operations: [
        new GetCollection(
            uriTemplate: '/rosters/{rosterId}/units',
            uriVariables: ['rosterId'],
            paginationEnabled: false,
            provider: RosterUnitProvider::class,
        ),
    ],
)]
class RosterUnit
{
    #[ApiProperty(identifier: true)]
    public readonly int $id;

    #[Groups('RosterUnit')]
    public readonly string $description;

    #[Groups('RosterUnit')]
    public readonly bool $markSupervisorsOnRoster;

    /** @var list<Position> */
    #[Groups('RosterUnit')]
    public readonly array $supervisors;

    /**
     * @param list<Soldier> $soldiers
     */
    public function __construct(
        #[Groups('RosterUnit')]
        public readonly Unit $unit,
        #[Groups('RosterUnit')]
        public readonly array $soldiers,
    ) {
        $this->id = $unit->getId();
        $this->description = $unit->getDescription();
        $this->markSupervisorsOnRoster = $unit->markSupervisorsOnRoster;
        $this->supervisors = array_values($unit->supervisors->toArray());
    }
}
