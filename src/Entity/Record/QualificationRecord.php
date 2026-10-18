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
use Forumify\Core\Entity\AuditableEntityInterface;
use Forumify\Milhq\Entity\CourseClass;
use Forumify\Milhq\Entity\Soldier;
use Forumify\Milhq\Entity\Qualification;
use Forumify\Milhq\Entity\QualificationTier;
use Forumify\Milhq\Api\Processor\RecordProcessor;
use Forumify\Milhq\Repository\QualificationRecordRepository;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: QualificationRecordRepository::class)]
#[ORM\Table('milhq_record_qualification')]
#[ApiResource(
    routePrefix: '/milhq',
    normalizationContext: ['groups' => ['Record', 'Record::read', 'MilhqSummary']],
    denormalizationContext: ['groups' => ['Record', 'Record::write']],
    order: ['createdAt' => 'DESC'],
    operations: [
        new Get(),
        new GetCollection(),
        new Post(
            security: 'is_granted("milhq.admin.records.qualification_records.create")',
            processor: RecordProcessor::class,
        ),
        new Delete(security: 'is_granted("milhq.admin.records.qualification_records.delete")'),
        new GetCollection(
            uriTemplate: '/soldiers/{soldierId}/qualification-records',
            uriVariables: [
                'soldierId' => new Link(fromClass: Soldier::class, toProperty: 'soldier'),
            ],
        ),
    ],
)]
class QualificationRecord implements RecordInterface, AuditableEntityInterface
{
    use RecordFields;

    #[ORM\ManyToOne(targetEntity: Soldier::class, inversedBy: 'qualificationRecords')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    #[Groups('Record')]
    private Soldier $soldier;

    #[ORM\ManyToOne(targetEntity: Qualification::class, fetch: 'EAGER')]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[Assert\NotNull]
    #[Groups('QualificationRecord')]
    private Qualification $qualification;

    #[ORM\ManyToOne(targetEntity: QualificationTier::class, fetch: 'EAGER')]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    #[Groups('QualificationRecord')]
    private ?QualificationTier $tier = null;

    #[ORM\ManyToOne(targetEntity: CourseClass::class)]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    private ?CourseClass $courseClass = null;

    public function getSoldier(): Soldier
    {
        return $this->soldier;
    }

    public function setSoldier(Soldier $soldier): void
    {
        $this->soldier = $soldier;
    }

    public function getQualification(): Qualification
    {
        return $this->qualification;
    }

    public function setQualification(Qualification $qualification): void
    {
        $this->qualification = $qualification;
    }

    public function getTier(): ?QualificationTier
    {
        return $this->tier;
    }

    public function setTier(?QualificationTier $tier): void
    {
        $this->tier = $tier;
    }

    public function getCourseClass(): ?CourseClass
    {
        return $this->courseClass;
    }

    public function setCourseClass(?CourseClass $courseClass): void
    {
        $this->courseClass = $courseClass;
    }

    #[Assert\Callback]
    public function validateTier(ExecutionContextInterface $context): void
    {
        if (!isset($this->qualification)) {
            return;
        }

        if ($this->tier === null) {
            if (!$this->qualification->tiers->isEmpty()) {
                $context->buildViolation('This qualification requires a tier to be selected.')->atPath('tier')->addViolation();
            }
            return;
        }

        if ($this->tier->parent->getId() !== $this->qualification->getId()) {
            $context->buildViolation('This tier does not belong to the selected qualification.')->atPath('tier')->addViolation();
        }
    }
}
