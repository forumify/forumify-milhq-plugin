<?php

declare(strict_types=1);

namespace Forumify\Milhq\Entity\Record;

use Doctrine\ORM\Mapping as ORM;
use Forumify\Core\Entity\AuditableEntityInterface;
use Forumify\Milhq\Entity\Soldier;
use Forumify\Milhq\Entity\Qualification;
use Forumify\Milhq\Entity\QualificationTier;
use Forumify\Milhq\Repository\QualificationRecordRepository;

#[ORM\Entity(repositoryClass: QualificationRecordRepository::class)]
#[ORM\Table('milhq_record_qualification')]
class QualificationRecord implements RecordInterface, AuditableEntityInterface
{
    use RecordFields;

    #[ORM\ManyToOne(targetEntity: Soldier::class, inversedBy: 'qualificationRecords')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Soldier $soldier;

    #[ORM\ManyToOne(targetEntity: Qualification::class)]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    private Qualification $qualification;

    #[ORM\ManyToOne(targetEntity: QualificationTier::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?QualificationTier $tier = null;

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
}
