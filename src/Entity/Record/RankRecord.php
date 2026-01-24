<?php

declare(strict_types=1);

namespace Forumify\Milhq\Entity\Record;

use Doctrine\ORM\Mapping as ORM;
use Forumify\Milhq\Entity\Soldier;
use Forumify\Milhq\Entity\Rank;
use Forumify\Milhq\Repository\RankRecordRepository;

#[ORM\Entity(repositoryClass: RankRecordRepository::class)]
#[ORM\Table('milhq_record_rank')]
class RankRecord implements RecordInterface
{
    use RecordFields;

    #[ORM\ManyToOne(targetEntity: Soldier::class, inversedBy: 'rankRecords')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Soldier $soldier;

    #[ORM\ManyToOne(targetEntity: Rank::class)]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    private Rank $rank;

    #[ORM\Column(length: 16)]
    private string $type = 'promotion';

    public function getRank(): Rank
    {
        return $this->rank;
    }

    public function setRank(Rank $rank): void
    {
        $this->rank = $rank;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }
}
