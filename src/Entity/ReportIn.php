<?php

declare(strict_types=1);

namespace Forumify\Milhq\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Forumify\Core\Entity\IdentifiableEntityTrait;
use Forumify\Milhq\Repository\ReportInRepository;

#[ORM\Entity(repositoryClass: ReportInRepository::class)]
#[ORM\Table('milhq_report_in')]
class ReportIn
{
    use IdentifiableEntityTrait;

    #[ORM\ManyToOne(targetEntity: Soldier::class)]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    private ?Soldier $soldier = null;

    #[ORM\Column(type: 'datetime')]
    private DateTime $lastReportInDate;

    #[ORM\ManyToOne(targetEntity: Status::class)]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    private ?Status $returnStatus = null;

    public function getSoldier(): ?Soldier
    {
        return $this->soldier;
    }

    public function setSoldier(Soldier $soldier): void
    {
        $this->soldier = $soldier;
    }

    public function getLastReportInDate(): DateTime
    {
        return $this->lastReportInDate;
    }

    public function setLastReportInDate(DateTime $lastReportInDate): void
    {
        $this->lastReportInDate = $lastReportInDate;
    }

    public function getReturnStatus(): ?Status
    {
        return $this->returnStatus;
    }

    public function setReturnStatus(?Status $returnStatus): void
    {
        $this->returnStatus = $returnStatus;
    }
}
