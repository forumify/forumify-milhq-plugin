<?php

declare(strict_types=1);

namespace Forumify\Milhq\Entity\Record;

use Doctrine\ORM\Mapping as ORM;
use Forumify\Milhq\Entity\Soldier;
use Forumify\Milhq\Repository\ServiceRecordRepository;

#[ORM\Entity(repositoryClass: ServiceRecordRepository::class)]
#[ORM\Table('milhq_record_service')]
class ServiceRecord implements RecordInterface
{
    use RecordFields;

    #[ORM\ManyToOne(targetEntity: Soldier::class, inversedBy: 'serviceRecords')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Soldier $soldier;
}
