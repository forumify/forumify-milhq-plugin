<?php

declare(strict_types=1);

namespace Forumify\Milhq\Entity\Record;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Forumify\Milhq\Entity\Soldier;
use Forumify\Milhq\Entity\Position;
use Forumify\Milhq\Entity\Specialty;
use Forumify\Milhq\Entity\Status;
use Forumify\Milhq\Entity\Unit;
use Forumify\Milhq\Repository\AssignmentRecordRepository;

#[ORM\Entity(repositoryClass: AssignmentRecordRepository::class)]
#[ORM\Index(fields: ['type'])]
#[ORM\Table('milhq_record_assignment')]
class AssignmentRecord implements RecordInterface
{
    use RecordFields;

    public const string TYPE_PRIMARY = 'primary';
    public const string TYPE_SECONDARY = 'secondary';

    #[Column(length: 16)]
    private string $type = self::TYPE_PRIMARY;

    #[ORM\ManyToOne(targetEntity: Status::class)]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    private ?Status $status = null;

    #[ORM\ManyToOne(targetEntity: Unit::class)]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    private ?Unit $unit = null;

    #[ORM\ManyToOne(targetEntity: Position::class)]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    private ?Position $position = null;

    #[ORM\ManyToOne(targetEntity: Specialty::class)]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    private ?Specialty $specialty = null;

    #[ORM\ManyToOne(targetEntity: Soldier::class, inversedBy: 'assignmentRecords')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Soldier $soldier;

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getStatus(): ?Status
    {
        return $this->status;
    }

    public function setStatus(?Status $status): void
    {
        $this->status = $status;
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
}
