<?php

declare(strict_types=1);

namespace Forumify\Milhq\Entity\Record;

use Doctrine\ORM\Mapping as ORM;
use Forumify\Milhq\Entity\Soldier;
use Forumify\Milhq\Repository\CombatRecordRepository;

#[ORM\Entity(repositoryClass: CombatRecordRepository::class)]
#[ORM\Table('milhq_record_combat')]
class CombatRecord implements RecordInterface
{
    use RecordFields;

    #[ORM\ManyToOne(targetEntity: Soldier::class, inversedBy: 'combatRecords')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Soldier $soldier;
}
