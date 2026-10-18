<?php

declare(strict_types=1);

namespace Forumify\Milhq\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Forumify\Api\Entity\NewAsset;
use Forumify\Api\Serializer\Attribute\Asset;
use Forumify\Core\Entity\AuditableEntityInterface;
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
use Forumify\Milhq\Api\Processor\SoldierProcessor;
use Forumify\Milhq\Repository\SoldierRepository;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SoldierRepository::class)]
#[ORM\Table(name: 'milhq_soldier')]
#[UniqueEntity('user', errorPath: 'user')]
#[ApiResource(
    routePrefix: '/milhq',
    normalizationContext: ['groups' => ['MilhqSummary']],
    operations: [
        new Get(),
        new GetCollection(),
        new Post(
            security: 'is_granted("milhq.admin.soldiers.create")',
            processor: SoldierProcessor::class,
        ),
        new Patch(
            security: 'is_granted("milhq.admin.soldiers.manage")',
            processor: SoldierProcessor::class,
        ),
        new Delete(security: 'is_granted("milhq.admin.soldiers.delete")'),
    ],
)]
class Soldier implements AuditableEntityInterface
{
    use IdentifiableEntityTrait;
    use TimestampableEntityTrait;

    #[ORM\OneToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    #[Groups(['Soldier', 'MilhqSummary'])]
    private ?User $user = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(allowNull: false)]
    #[Groups(['Soldier', 'MilhqSummary'])]
    private string $name;

    #[ORM\ManyToOne(targetEntity: Rank::class, fetch: 'EAGER')]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    #[Groups(['Soldier', 'MilhqSummary'])]
    private ?Rank $rank = null;

    #[ORM\ManyToOne(targetEntity: Unit::class, inversedBy: 'soldiers', fetch: 'EAGER')]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    #[Groups(['Soldier::read', 'MilhqSummary'])]
    private ?Unit $unit = null;

    #[ORM\ManyToOne(targetEntity: Position::class, fetch: 'EAGER')]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    #[Groups(['Soldier::read', 'MilhqSummary'])]
    private ?Position $position = null;

    #[ORM\ManyToOne(targetEntity: Specialty::class, fetch: 'EAGER')]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    #[Groups(['Soldier::read', 'MilhqSummary'])]
    private ?Specialty $specialty = null;

    #[ORM\ManyToOne(targetEntity: Status::class, fetch: 'EAGER')]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    #[Groups(['Soldier::read', 'MilhqSummary'])]
    private ?Status $status = null;

    #[ORM\Column(nullable: true)]
    #[Groups('Soldier')]
    private ?string $signature = null;

    #[Asset('signature', 'milhq.asset', 'milhq_asset.storage')]
    public ?NewAsset $newSignature = null;

    #[ORM\Column(nullable: true)]
    #[Groups('Soldier')]
    private ?string $uniform = null;

    #[Asset('uniform', 'milhq.asset', 'milhq_asset.storage')]
    public ?NewAsset $newUniform = null;

    /** @var Collection<int, ServiceRecord> */
    #[ORM\OneToMany(mappedBy: 'soldier', targetEntity: ServiceRecord::class, fetch: 'EXTRA_LAZY')]
    private Collection $serviceRecords;

    /** @var Collection<int, AwardRecord> */
    #[ORM\OneToMany(mappedBy: 'soldier', targetEntity: AwardRecord::class, fetch: 'EXTRA_LAZY')]
    private Collection $awardRecords;

    /** @var Collection<int, CombatRecord> */
    #[ORM\OneToMany(mappedBy: 'soldier', targetEntity: CombatRecord::class, fetch: 'EXTRA_LAZY')]
    private Collection $combatRecords;

    /** @var Collection<int, RankRecord> */
    #[ORM\OneToMany(mappedBy: 'soldier', targetEntity: RankRecord::class, fetch: 'EXTRA_LAZY')]
    private Collection $rankRecords;

    /** @var Collection<int, AssignmentRecord> */
    #[ORM\OneToMany(mappedBy: 'soldier', targetEntity: AssignmentRecord::class, fetch: 'EXTRA_LAZY')]
    private Collection $assignmentRecords;

    /** @var Collection<int, QualificationRecord> */
    #[ORM\OneToMany(mappedBy: 'soldier', targetEntity: QualificationRecord::class, fetch: 'EXTRA_LAZY')]
    private Collection $qualificationRecords;

    #[ORM\OneToOne(targetEntity: Topic::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    #[Groups('Soldier::read')]
    private ?Topic $enlistmentTopic = null;

    #[ORM\Column(type: Types::BIGINT, nullable: true, options: ['unsigned' => true])]
    #[Groups('Soldier')]
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

    /**
     * @return Collection<int, ServiceRecord>
     */
    public function getServiceRecords(): Collection
    {
        return $this->serviceRecords;
    }

    /**
     * @param Collection<int, ServiceRecord> $serviceRecords
     */
    public function setServiceRecords(Collection $serviceRecords): void
    {
        $this->serviceRecords = $serviceRecords;
    }

    /**
     * @return Collection<int, AwardRecord>
     */
    public function getAwardRecords(): Collection
    {
        return $this->awardRecords;
    }

    /**
     * @param Collection<int, AwardRecord> $awardRecords
     */
    public function setAwardRecords(Collection $awardRecords): void
    {
        $this->awardRecords = $awardRecords;
    }

    /**
     * @return Collection<int, CombatRecord>
     */
    public function getCombatRecords(): Collection
    {
        return $this->combatRecords;
    }

    /**
     * @param Collection<int, CombatRecord> $combatRecords
     */
    public function setCombatRecords(Collection $combatRecords): void
    {
        $this->combatRecords = $combatRecords;
    }

    /**
     * @return Collection<int, RankRecord>
     */
    public function getRankRecords(): Collection
    {
        return $this->rankRecords;
    }

    /**
     * @param Collection<int, RankRecord> $rankRecords
     */
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

    /**
     * @param Collection<int, AssignmentRecord> $assignmentRecords
     */
    public function setAssignmentRecords(Collection $assignmentRecords): void
    {
        $this->assignmentRecords = $assignmentRecords;
    }

    /**
     * @return Collection<int, QualificationRecord>
     */
    public function getQualificationRecords(): Collection
    {
        return $this->qualificationRecords;
    }

    /**
     * @param Collection<int, QualificationRecord> $qualificationRecords
     */
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

    public function getIdentifierForAudit(): string
    {
        return (string)$this->getId();
    }

    public function getNameForAudit(): string
    {
        return $this->getName();
    }
}
