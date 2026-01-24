<?php

declare(strict_types=1);

namespace Forumify\Milhq\Entity;

use Doctrine\ORM\Mapping as ORM;
use Forumify\Core\Entity\BlameableEntityTrait;
use Forumify\Core\Entity\IdentifiableEntityTrait;
use Forumify\Core\Entity\TimestampableEntityTrait;
use Forumify\Milhq\Repository\AfterActionReportRepository;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: AfterActionReportRepository::class)]
#[ORM\Table('milhq_after_action_report')]
#[ORM\UniqueConstraint(fields: ['mission', 'unit'])]
#[UniqueEntity(['mission', 'unit'], message: 'An AAR already exists for this unit.', errorPath: 'unit')]
class AfterActionReport
{
    use IdentifiableEntityTrait;
    use BlameableEntityTrait;
    use TimestampableEntityTrait;

    #[ORM\ManyToOne(targetEntity: Unit::class)]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    private ?Unit $unit = null;

    #[ORM\Column(type: 'text')]
    private string $report;

    #[ORM\Column(type: 'json')]
    private array $attendance;

    #[ORM\ManyToOne(targetEntity: Mission::class, fetch: 'EXTRA_LAZY', inversedBy: 'afterActionReports')]
    #[ORM\JoinColumn(onDelete: 'CASCADE', nullable: false)]
    private Mission $mission;

    public function getUnit(): ?Unit
    {
        return $this->unit;
    }

    public function setUnit(Unit $unit): void
    {
        $this->unit = $unit;
    }

    public function getReport(): string
    {
        return $this->report;
    }

    public function setReport(string $report): void
    {
        $this->report = $report;
    }

    public function getAttendance(): array
    {
        return $this->attendance;
    }

    public function setAttendance(array $attendance): void
    {
        $this->attendance = $attendance;
    }

    public function getMission(): Mission
    {
        return $this->mission;
    }

    public function setMission(Mission $mission): void
    {
        $this->mission = $mission;
    }
}
