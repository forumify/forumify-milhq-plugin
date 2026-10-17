<?php

declare(strict_types=1);

namespace Forumify\Milhq\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\CreateProvider;
use Doctrine\ORM\Mapping as ORM;
use Forumify\Core\Entity\IdentifiableEntityTrait;
use Forumify\Milhq\Api\Processor\MissionRsvpProcessor;
use Forumify\Milhq\Repository\MissionRSVPRepository;
use Forumify\Plugin\Attribute\PluginVersion;
use Symfony\Component\Serializer\Attribute\Groups;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[PluginVersion('forumify/forumify-milhq-plugin', 'premium')]
#[ORM\Entity(repositoryClass: MissionRSVPRepository::class)]
#[ORM\Table('milhq_mission_rsvp')]
#[ApiResource(
    routePrefix: '/milhq',
    normalizationContext: ['groups' => ['MilhqSummary']],
    operations: [
        new Get(extraProperties: ['acl' => ['permission' => 'view_missions', 'entity' => 'mission.operation']]),
        new Delete(
            security: 'object.getSoldier()?.getUser()?.getId() == user.getUserId() or is_granted("ACCESS_CONTROL_LIST", {"permission": "manage_missions", "entity": object.getMission().getOperation()})',
        ),
        new GetCollection(
            uriTemplate: '/missions/{missionId}/rsvps',
            uriVariables: [
                'missionId' => new Link(fromClass: Mission::class, toProperty: 'mission'),
            ],
            extraProperties: ['acl' => ['permission' => 'view_missions', 'entity' => 'mission.operation']],
        ),
        new Post(
            uriTemplate: '/missions/{missionId}/rsvps',
            uriVariables: [
                'missionId' => new Link(
                    fromClass: Mission::class,
                    toProperty: 'mission',
                    security: 'is_granted("ACCESS_CONTROL_LIST", {"permission": "view_missions", "entity": mission.getOperation()})',
                ),
            ],
            provider: CreateProvider::class,
            processor: MissionRsvpProcessor::class,
        ),
    ],
)]
class MissionRsvp
{
    use IdentifiableEntityTrait;
    use TimestampableEntity;

    #[ORM\ManyToOne(targetEntity: Soldier::class)]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[Groups('MissionRsvp::read')]
    private ?Soldier $soldier = null;

    #[ORM\ManyToOne(Mission::class, fetch: 'EXTRA_LAZY', inversedBy: 'rsvps')]
    #[ORM\JoinColumn(onDelete: 'CASCADE', nullable: false)]
    #[Groups('MissionRsvp::read')]
    private Mission $mission;

    #[ORM\Column(type: 'boolean')]
    #[Groups('MissionRsvp')]
    private bool $going = false;

    public function getSoldier(): ?Soldier
    {
        return $this->soldier;
    }

    public function setSoldier(Soldier $user): void
    {
        $this->soldier = $user;
    }

    public function getMission(): Mission
    {
        return $this->mission;
    }

    public function setMission(Mission $mission): void
    {
        $this->mission = $mission;
    }

    public function isGoing(): bool
    {
        return $this->going;
    }

    public function setGoing(bool $going): void
    {
        $this->going = $going;
    }
}
