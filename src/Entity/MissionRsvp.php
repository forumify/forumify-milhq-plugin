<?php

declare(strict_types=1);

namespace Forumify\Milhq\Entity;

use Doctrine\ORM\Mapping as ORM;
use Forumify\Core\Entity\IdentifiableEntityTrait;
use Forumify\Milhq\Repository\MissionRSVPRepository;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity(repositoryClass: MissionRSVPRepository::class)]
#[ORM\Table('milhq_mission_rsvp')]
class MissionRsvp
{
    use IdentifiableEntityTrait;
    use TimestampableEntity;

    #[ORM\ManyToOne(targetEntity: Soldier::class)]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    private ?Soldier $soldier = null;

    #[ORM\ManyToOne(Mission::class, fetch: 'EXTRA_LAZY', inversedBy: 'rsvps')]
    #[ORM\JoinColumn(onDelete: 'CASCADE', nullable: false)]
    private Mission $mission;

    #[ORM\Column(type: 'boolean')]
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
