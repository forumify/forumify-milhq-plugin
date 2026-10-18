<?php

declare(strict_types=1);

namespace Forumify\Milhq\Entity;

use Doctrine\ORM\Mapping as ORM;
use Forumify\Core\Entity\IdentifiableEntityTrait;
use Forumify\Milhq\Repository\AfterActionReportAttendanceRepository;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AfterActionReportAttendanceRepository::class)]
#[ORM\Table('milhq_after_action_report_attendance')]
#[ORM\UniqueConstraint(fields: ['afterActionReport', 'soldier'])]
class AfterActionReportAttendance
{
    use IdentifiableEntityTrait;

    #[ORM\ManyToOne(targetEntity: AfterActionReport::class, inversedBy: 'attendance')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private AfterActionReport $afterActionReport;

    #[ORM\ManyToOne(targetEntity: Soldier::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups('AfterActionReport')]
    private Soldier $soldier;

    #[ORM\Column]
    #[Assert\NotBlank]
    #[Groups('AfterActionReport')]
    private string $state;

    public function __construct(Soldier $soldier, string $state)
    {
        $this->soldier = $soldier;
        $this->state = $state;
    }

    public function getAfterActionReport(): AfterActionReport
    {
        return $this->afterActionReport;
    }

    public function setAfterActionReport(AfterActionReport $afterActionReport): void
    {
        $this->afterActionReport = $afterActionReport;
    }

    public function getSoldier(): Soldier
    {
        return $this->soldier;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function setState(string $state): void
    {
        $this->state = $state;
    }
}
