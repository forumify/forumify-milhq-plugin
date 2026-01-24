<?php

declare(strict_types=1);

namespace Forumify\Milhq\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Forumify\Core\Entity\AccessControlledEntityInterface;
use Forumify\Core\Entity\ACLParameters;
use Forumify\Core\Entity\IdentifiableEntityTrait;
use Forumify\Core\Entity\SluggableEntityTrait;
use Forumify\Core\Entity\TimestampableEntityTrait;
use Forumify\Milhq\Repository\OperationRepository;

#[ORM\Entity(repositoryClass: OperationRepository::class)]
#[ORM\Table('milhq_operation')]
class Operation implements AccessControlledEntityInterface
{
    use IdentifiableEntityTrait;
    use SluggableEntityTrait;
    use TimestampableEntityTrait;

    #[ORM\Column(length: 255)]
    private string $title;

    #[ORM\Column(type: 'text')]
    private string $description;

    #[ORM\Column(type: 'text')]
    private string $content = '';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?DateTime $start = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?DateTime $end = null;

    #[ORM\Column(type: 'text')]
    private string $missionBriefingTemplate = '';

    #[ORM\Column(type: 'text')]
    private string $afterActionReportTemplate = '';

    #[ORM\OneToMany(mappedBy: 'operation', targetEntity: Mission::class, cascade: ['persist', 'remove'])]
    private Collection $missions;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $requestRsvp = false;

    public function __construct()
    {
        $this->missions = new ArrayCollection();
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): void
    {
        $this->image = $image;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getStart(): ?DateTime
    {
        return $this->start;
    }

    public function setStart(?DateTime $start): void
    {
        $this->start = $start;
    }

    public function getEnd(): ?DateTime
    {
        return $this->end;
    }

    public function setEnd(?DateTime $end): void
    {
        $this->end = $end;
    }

    /**
     * @return Collection<Mission>
     */
    public function getMissions(): Collection
    {
        return $this->missions;
    }

    public function setMissions(Collection $missions): void
    {
        $this->missions = $missions;
    }

    public function getMissionBriefingTemplate(): string
    {
        return $this->missionBriefingTemplate;
    }

    public function setMissionBriefingTemplate(string $missionBriefingTemplate): void
    {
        $this->missionBriefingTemplate = $missionBriefingTemplate;
    }

    public function getAfterActionReportTemplate(): string
    {
        return $this->afterActionReportTemplate;
    }

    public function setAfterActionReportTemplate(string $afterActionReportTemplate): void
    {
        $this->afterActionReportTemplate = $afterActionReportTemplate;
    }

    public function isRequestRsvp(): bool
    {
        return $this->requestRsvp;
    }

    public function setRequestRsvp(bool $requestRsvp): void
    {
        $this->requestRsvp = $requestRsvp;
    }

    public function getACLPermissions(): array
    {
        return [
            'view',
            'view_missions',
            'manage_missions',
            'view_after_action_reports',
            'manage_after_action_reports',
        ];
    }

    public function getACLParameters(): ACLParameters
    {
        return new ACLParameters(
            self::class,
            (string)$this->getId(),
            'milhq_admin_operations_list',
            ['id' => $this->getId()],
        );
    }
}
