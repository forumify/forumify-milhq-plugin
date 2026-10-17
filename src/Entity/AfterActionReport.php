<?php

declare(strict_types=1);

namespace Forumify\Milhq\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\CreateProvider;
use Doctrine\ORM\Mapping as ORM;
use Forumify\Core\Entity\AuditableEntityInterface;
use Forumify\Core\Entity\BlameableEntityTrait;
use Forumify\Core\Entity\IdentifiableEntityTrait;
use Forumify\Core\Entity\TimestampableEntityTrait;
use Forumify\Milhq\Api\Processor\AfterActionReportProcessor;
use Forumify\Milhq\Repository\AfterActionReportRepository;
use Forumify\Plugin\Attribute\PluginVersion;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[PluginVersion('forumify/forumify-milhq-plugin', 'premium')]
#[ORM\Entity(repositoryClass: AfterActionReportRepository::class)]
#[ORM\Table('milhq_after_action_report')]
#[ORM\UniqueConstraint(fields: ['mission', 'unit'])]
#[UniqueEntity(['mission', 'unit'], message: 'An AAR already exists for this unit.', errorPath: 'unit')]
#[ApiResource(
    routePrefix: '/milhq',
    normalizationContext: ['groups' => ['MilhqSummary']],
    operations: [
        new Get(extraProperties: ['acl' => ['permission' => 'view_after_action_reports', 'entity' => 'mission.operation']]),
        new GetCollection(extraProperties: ['acl' => ['permission' => 'view_after_action_reports', 'entity' => 'mission.operation']]),
        new Patch(
            extraProperties: ['acl' => ['permission' => 'manage_after_action_reports', 'entity' => 'mission.operation']],
            processor: AfterActionReportProcessor::class,
        ),
        new Delete(extraProperties: ['acl' => ['permission' => 'manage_after_action_reports', 'entity' => 'mission.operation']]),
        new GetCollection(
            uriTemplate: '/missions/{missionId}/after-action-reports',
            uriVariables: [
                'missionId' => new Link(fromClass: Mission::class, toProperty: 'mission'),
            ],
            extraProperties: ['acl' => ['permission' => 'view_after_action_reports', 'entity' => 'mission.operation']],
        ),
        new Post(
            uriTemplate: '/missions/{missionId}/after-action-reports',
            uriVariables: [
                'missionId' => new Link(
                    fromClass: Mission::class,
                    toProperty: 'mission',
                    security: 'is_granted("ACCESS_CONTROL_LIST", {"permission": "manage_after_action_reports", "entity": mission.getOperation()})',
                ),
            ],
            provider: CreateProvider::class,
            processor: AfterActionReportProcessor::class,
        ),
    ],
)]
class AfterActionReport implements AuditableEntityInterface
{
    use IdentifiableEntityTrait;
    use BlameableEntityTrait;
    use TimestampableEntityTrait;

    #[ORM\ManyToOne(targetEntity: Unit::class)]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[Assert\NotNull]
    #[Groups('AfterActionReport')]
    private ?Unit $unit = null;

    #[ORM\Column(type: 'text')]
    #[Groups('AfterActionReport')]
    private string $report = '';

    /** @var array<string, list<int>> */
    #[ORM\Column(type: 'json')]
    #[Groups('AfterActionReport')]
    private array $attendance = [];

    #[ORM\ManyToOne(targetEntity: Mission::class, fetch: 'EXTRA_LAZY', inversedBy: 'afterActionReports')]
    #[ORM\JoinColumn(onDelete: 'CASCADE', nullable: false)]
    #[Groups('AfterActionReport::read')]
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

    public function getIdentifierForAudit(): string
    {
        return (string)$this->getId();
    }

    public function getNameForAudit(): string
    {
        return $this->getMission()->getTitle();
    }
}
