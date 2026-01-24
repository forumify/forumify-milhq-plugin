<?php

declare(strict_types=1);

namespace Forumify\Milhq\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Forumify\Core\Entity\IdentifiableEntityTrait;
use Forumify\Core\Entity\TimestampableEntityTrait;
use Forumify\Core\Entity\User;
use Forumify\Forum\Entity\Topic;
use Forumify\Milhq\Entity\Record\AssignmentRecord;
use Forumify\Milhq\Entity\Record\AwardRecord;
use Forumify\Milhq\Entity\Record\CombatRecord;
use Forumify\Milhq\Entity\Record\QualificationRecord;
use Forumify\Milhq\Entity\Record\RankRecord;
use Forumify\Milhq\Entity\Record\ServiceRecord;
use Forumify\Milhq\Repository\SoldierRepository;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: SoldierRepository::class)]
#[ORM\Table(name: 'milhq_soldier')]
#[UniqueEntity('user', errorPath: 'user')]
class Soldier
{
    use IdentifiableEntityTrait;
    use TimestampableEntityTrait;

    #[ORM\OneToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?User $user = null;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\ManyToOne(targetEntity: Rank::class, fetch: 'EAGER')]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?Rank $rank = null;

    #[ORM\ManyToOne(targetEntity: Unit::class, inversedBy: 'soldiers', fetch: 'EAGER')]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?Unit $unit = null;

    #[ORM\ManyToOne(targetEntity: Position::class, fetch: 'EAGER')]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?Position $position = null;

    #[ORM\ManyToOne(targetEntity: Specialty::class, fetch: 'EAGER')]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?Specialty $specialty = null;

    #[ORM\ManyToOne(targetEntity: Status::class, fetch: 'EAGER')]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?Status $status = null;

    #[ORM\Column(nullable: true)]
    private ?string $signature = null;

    #[ORM\Column(nullable: true)]
    private ?string $uniform = null;

    #[ORM\OneToMany(mappedBy: 'soldier', targetEntity: ServiceRecord::class, fetch: 'EXTRA_LAZY')]
    private Collection $serviceRecords;

    #[ORM\OneToMany(mappedBy: 'soldier', targetEntity: AwardRecord::class, fetch: 'EXTRA_LAZY')]
    private Collection $awardRecords;

    #[ORM\OneToMany(mappedBy: 'soldier', targetEntity: CombatRecord::class, fetch: 'EXTRA_LAZY')]
    private Collection $combatRecords;

    #[ORM\OneToMany(mappedBy: 'soldier', targetEntity: RankRecord::class, fetch: 'EXTRA_LAZY')]
    private Collection $rankRecords;

    #[ORM\OneToMany(mappedBy: 'soldier', targetEntity: AssignmentRecord::class, fetch: 'EXTRA_LAZY')]
    private Collection $assignmentRecords;

    #[ORM\OneToMany(mappedBy: 'soldier', targetEntity: QualificationRecord::class, fetch: 'EXTRA_LAZY')]
    private Collection $qualificationRecords;

    #[ORM\OneToOne(targetEntity: Topic::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?Topic $enlistmentTopic = null;

    #[ORM\Column(type: Types::BIGINT, nullable: true, options: ['unsigned' => true])]
    private ?int $steamId = null;

    public function __construct()
    {
        $this->serviceRecords = new ArrayCollection();
        $this->awardRecords = new ArrayCollection();
        $this->combatRecords = new ArrayCollection();
        $this->rankRecords = new ArrayCollection();
        $this->assignmentRecords = new ArrayCollection();
        $this->qualificationRecords = new ArrayCollection();
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): void
    {
        $this->user = $user;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getRank(): ?Rank
    {
        return $this->rank;
    }

    public function setRank(?Rank $rank): void
    {
        $this->rank = $rank;
    }

    public function getUnit(): ?Unit
    {
        return $this->unit;
    }

    public function setUnit(?Unit $unit): void
    {
        $this->unit = $unit;
    }

    public function getPosition(): ?Position
    {
        return $this->position;
    }

    public function setPosition(?Position $position): void
    {
        $this->position = $position;
    }

    public function getSpecialty(): ?Specialty
    {
        return $this->specialty;
    }

    public function setSpecialty(?Specialty $specialty): void
    {
        $this->specialty = $specialty;
    }

    public function getStatus(): ?Status
    {
        return $this->status;
    }

    public function setStatus(?Status $status): void
    {
        $this->status = $status;
    }

    public function getSignature(): ?string
    {
        return $this->signature;
    }

    public function setSignature(?string $signature): void
    {
        $this->signature = $signature;
    }

    public function getUniform(): ?string
    {
        return $this->uniform;
    }

    public function setUniform(?string $uniform): void
    {
        $this->uniform = $uniform;
    }

    public function getServiceRecords(): Collection
    {
        return $this->serviceRecords;
    }

    public function setServiceRecords(Collection $serviceRecords): void
    {
        $this->serviceRecords = $serviceRecords;
    }

    public function getAwardRecords(): Collection
    {
        return $this->awardRecords;
    }

    public function setAwardRecords(Collection $awardRecords): void
    {
        $this->awardRecords = $awardRecords;
    }

    public function getCombatRecords(): Collection
    {
        return $this->combatRecords;
    }

    public function setCombatRecords(Collection $combatRecords): void
    {
        $this->combatRecords = $combatRecords;
    }

    public function getRankRecords(): Collection
    {
        return $this->rankRecords;
    }

    public function setRankRecords(Collection $rankRecords): void
    {
        $this->rankRecords = $rankRecords;
    }

    /**
     * @return Collection<int, AssignmentRecord>
     */
    public function getAssignmentRecords(): Collection
    {
        return $this->assignmentRecords;
    }

    public function setAssignmentRecords(Collection $assignmentRecords): void
    {
        $this->assignmentRecords = $assignmentRecords;
    }

    public function getQualificationRecords(): Collection
    {
        return $this->qualificationRecords;
    }

    public function setQualificationRecords(Collection $qualificationRecords): void
    {
        $this->qualificationRecords = $qualificationRecords;
    }

    public function getEnlistmentTopic(): ?Topic
    {
        return $this->enlistmentTopic;
    }

    public function setEnlistmentTopic(?Topic $enlistmentTopic): void
    {
        $this->enlistmentTopic = $enlistmentTopic;
    }

    public function getSteamId(): ?int
    {
        return $this->steamId;
    }

    public function setSteamId(?int $steamId): void
    {
        $this->steamId = $steamId;
    }
}
