<?php

declare(strict_types=1);

namespace Forumify\Milhq\Entity\Record;

use Doctrine\ORM\Mapping as ORM;
use Forumify\Milhq\Entity\Soldier;
use Forumify\Milhq\Entity\Qualification;
use Forumify\Milhq\Repository\QualificationRecordRepository;

#[ORM\Entity(repositoryClass: QualificationRecordRepository::class)]
#[ORM\Table('milhq_record_qualification')]
class QualificationRecord implements RecordInterface
{
    use RecordFields;

    #[ORM\ManyToOne(targetEntity: Soldier::class, inversedBy: 'qualificationRecords')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Soldier $soldier;

    #[ORM\ManyToOne(targetEntity: Qualification::class)]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    private Qualification $qualification;

    public function getQualification(): Qualification
    {
        return $this->qualification;
    }

    public function setQualification(Qualification $qualification): void
    {
        $this->qualification = $qualification;
    }
}
