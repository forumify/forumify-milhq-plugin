<?php

declare(strict_types=1);

namespace Forumify\Milhq\Entity\Record;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Post;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Forumify\Core\Entity\AuditableEntityInterface;
use Forumify\Milhq\Entity\Soldier;
use Forumify\Milhq\Entity\Position;
use Forumify\Milhq\Entity\Specialty;
use Forumify\Milhq\Entity\Status;
use Forumify\Milhq\Entity\Unit;
use Forumify\Milhq\Api\Processor\RecordProcessor;
use Forumify\Milhq\Repository\AssignmentRecordRepository;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AssignmentRecordRepository::class)]
#[ORM\Index(fields: ['type'])]
#[ORM\Index(fields: ['soldier', 'type'])]
#[ORM\Table('milhq_record_assignment')]
#[ApiResource(
    routePrefix: '/milhq',
    normalizationContext: ['groups' => ['Record', 'Record::read', 'MilhqSummary']],
    denormalizationContext: ['groups' => ['Record', 'Record::write']],
    order: ['createdAt' => 'DESC'],
    operations: [
        new Get(),
        new GetCollection(),
        new Post(
            security: 'is_granted("milhq.admin.records.assignment_records.create")',
            processor: RecordProcessor::class,
        ),
        new Delete(security: 'is_granted("milhq.admin.records.assignment_records.delete")'),
        new GetCollection(
            uriTemplate: '/soldiers/{soldierId}/assignment-records',
            uriVariables: [
                'soldierId' => new Link(fromClass: Soldier::class, toProperty: 'soldier'),
            ],
        ),
    ],
)]
class AssignmentRecord implements RecordInterface, AuditableEntityInterface
{
    use RecordFields;

    public const string TYPE_PRIMARY = 'primary';
    public const string TYPE_SECONDARY = 'secondary';

    #[Assert\Choice([self::TYPE_PRIMARY, self::TYPE_SECONDARY])]
    #[Groups('AssignmentRecord')]
    #[Column(length: 16)]
    private string $type = self::TYPE_PRIMARY;

    #[ORM\ManyToOne(targetEntity: Status::class, fetch: 'EAGER')]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[Groups('AssignmentRecord')]
    private ?Status $status = null;

    #[ORM\ManyToOne(targetEntity: Unit::class, fetch: 'EAGER')]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[Groups('AssignmentRecord')]
    private ?Unit $unit = null;

    #[ORM\ManyToOne(targetEntity: Position::class, fetch: 'EAGER')]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[Groups('AssignmentRecord')]
    private ?Position $position = null;

    #[ORM\ManyToOne(targetEntity: Specialty::class, fetch: 'EAGER')]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[Groups('AssignmentRecord')]
    private ?Specialty $specialty = null;

    #[ORM\ManyToOne(targetEntity: Soldier::class, inversedBy: 'assignmentRecords')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    #[Groups('Record')]
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
