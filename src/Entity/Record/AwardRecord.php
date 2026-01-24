<?php

declare(strict_types=1);

namespace Forumify\Milhq\Entity\Record;

use Doctrine\ORM\Mapping as ORM;
use Forumify\Milhq\Entity\Award;
use Forumify\Milhq\Entity\Soldier;
use Forumify\Milhq\Repository\AwardRecordRepository;

#[ORM\Entity(repositoryClass: AwardRecordRepository::class)]
#[ORM\Table('milhq_record_award')]
class AwardRecord implements RecordInterface
{
    use RecordFields;

    #[ORM\ManyToOne(targetEntity: Soldier::class, inversedBy: 'awardRecords')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Soldier $soldier;

    #[ORM\ManyToOne(targetEntity: Award::class)]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    private Award $award;

    public function getAward(): Award
    {
        return $this->award;
    }

    public function setAward(Award $award): void
    {
        $this->award = $award;
    }
}
