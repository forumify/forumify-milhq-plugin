<?php

declare(strict_types=1);

namespace Forumify\Milhq\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Forumify\Core\Entity\BlameableEntityTrait;
use Forumify\Core\Entity\IdentifiableEntityTrait;
use Forumify\Core\Entity\SluggableEntityTrait;
use Forumify\Core\Entity\TimestampableEntityTrait;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

trait MissionFields
{
    use IdentifiableEntityTrait;
    use SluggableEntityTrait;
    use BlameableEntityTrait;
    use TimestampableEntityTrait;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(allowNull: false)]
    #[Groups(['Mission', 'MilhqSummary'])]
    private string $title;

    #[ORM\Column(type: 'text')]
    #[Groups('Mission')]
    private string $briefing = '';

    #[ORM\Column(type: 'datetime')]
    #[Assert\NotNull]
    #[Groups(['Mission', 'MilhqSummary'])]
    private DateTime $start;

    #[ORM\Column(type: 'datetime', nullable: true)]
    #[Groups(['Mission', 'MilhqSummary'])]
    private ?DateTime $end = null;

    #[ORM\ManyToOne(targetEntity: Operation::class, fetch: 'EXTRA_LAZY', inversedBy: 'missions')]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[Groups('Mission::read')]
    private Operation $operation;

    #[ORM\OneToMany(mappedBy: 'mission', targetEntity: AfterActionReport::class, cascade: ['persist', 'remove'])]
    private Collection $afterActionReports;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups('Mission')]
    private bool $sendNotification = false;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups('Mission')]
    private bool $createCombatRecords = false;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups('Mission')]
    private ?string $combatRecordText = null;

    #[ORM\OneToMany(mappedBy: 'mission', targetEntity: MissionRsvp::class, fetch: 'EXTRA_LAZY', cascade: ['persist', 'remove'])]
    private Collection $rsvps;

    public function __construct()
    {
        $this->afterActionReports = new ArrayCollection();
        $this->rsvps = new ArrayCollection();
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getBriefing(): string
    {
        return $this->briefing;
    }

    public function setBriefing(string $briefing): void
    {
        $this->briefing = $briefing;
    }

    public function getStart(): DateTime
    {
        return $this->start;
    }

    public function setStart(DateTime $start): void
    {
        $this->start = $start;
    }

    public function getEnd(): ?DateTime
    {
        return $this->end;
    }

    public function setEnd(?DateTime $end): void
    {
        $this->end = $end;
    }

    public function getOperation(): Operation
    {
        return $this->operation;
    }

    public function setOperation(Operation $operation): void
    {
        $this->operation = $operation;
    }

    /**
     * @return Collection<AfterActionReport>
     */
    public function getAfterActionReports(): Collection
    {
        return $this->afterActionReports;
    }

    public function setAfterActionReports(Collection $afterActionReports): void
    {
        $this->afterActionReports = $afterActionReports;
    }

    public function isSendNotification(): bool
    {
        return $this->sendNotification;
    }

    public function setSendNotification(bool $sendNotification): void
    {
        $this->sendNotification = $sendNotification;
    }

    public function isCreateCombatRecords(): bool
    {
        return $this->createCombatRecords;
    }

    public function setCreateCombatRecords(bool $createCombatRecords): void
    {
        $this->createCombatRecords = $createCombatRecords;
    }

    public function getCombatRecordText(): ?string
    {
        return $this->combatRecordText;
    }

    public function setCombatRecordText(?string $combatRecordText): void
    {
        $this->combatRecordText = $combatRecordText;
    }

    public function canRsvp(): bool
    {
        return $this->getStart() > new DateTime();
    }

    /**
     * @return Collection<int, MissionRsvp>
     */
    public function getRsvps(): Collection
    {
        return $this->rsvps;
    }

    public function setRsvps(Collection $rsvps): void
    {
        $this->rsvps = $rsvps;
    }

    public function getIdentifierForAudit(): string
    {
        return (string)$this->getId();
    }

    public function getNameForAudit(): string
    {
        return $this->getTitle();
    }
}
