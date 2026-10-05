<?php

declare(strict_types=1);

namespace Forumify\Milhq\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Forumify\Core\Entity\AccessControlledEntityInterface;
use Forumify\Core\Entity\ACLParameters;
use Forumify\Core\Entity\AuditableEntityInterface;
use Forumify\Core\Entity\IdentifiableEntityTrait;
use Forumify\Core\Entity\TimestampableEntityTrait;
use Forumify\Milhq\Repository\FormRepository;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: FormRepository::class)]
#[ORM\Table('milhq_form')]
class Form implements AccessControlledEntityInterface, AuditableEntityInterface
{
    use IdentifiableEntityTrait;
    use TimestampableEntityTrait;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(allowNull: false)]
    private string $name;

    #[ORM\Column(type: Types::TEXT)]
    private string $successMessage = '';

    #[ORM\ManyToOne(targetEntity: FormStatus::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?FormStatus $defaultStatus = null;

    #[ORM\Column(type: Types::TEXT)]
    private string $description = '';

    #[ORM\Column(type: Types::TEXT)]
    private string $instructions = '';

    #[ORM\OneToMany(mappedBy: 'form', targetEntity: FormSubmission::class)]
    private Collection $submissions;

    #[ORM\OneToMany(mappedBy: 'form', targetEntity: FormField::class, cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $fields;

    /**
     * @var Collection<int, FormStatus>
     */
    #[ORM\OneToMany(mappedBy: 'form', targetEntity: FormStatus::class, cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $statuses;

    public function __construct()
    {
        $this->submissions = new ArrayCollection();
        $this->fields = new ArrayCollection();
        $this->statuses = new ArrayCollection();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getSuccessMessage(): string
    {
        return $this->successMessage;
    }

    public function setSuccessMessage(string $successMessage): void
    {
        $this->successMessage = $successMessage;
    }

    public function getDefaultStatus(): ?FormStatus
    {
        return $this->defaultStatus;
    }

    public function setDefaultStatus(?FormStatus $defaultStatus): void
    {
        $this->defaultStatus = $defaultStatus;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getInstructions(): string
    {
        return $this->instructions;
    }

    public function setInstructions(string $instructions): void
    {
        $this->instructions = $instructions;
    }

    public function getSubmissions(): Collection
    {
        return $this->submissions;
    }

    public function setSubmissions(Collection $submissions): void
    {
        $this->submissions = $submissions;
    }

    public function addSubmission(FormSubmission $submission): void
    {
        $this->submissions->add($submission);
    }

    public function removeSubmission(FormSubmission $submission): void
    {
        $this->submissions->removeElement($submission);
    }

    /**
     * @return Collection<int, FormField>
     */
    public function getFields(): Collection
    {
        return $this->fields;
    }

    public function setFields(Collection $fields): void
    {
        $this->fields = $fields;
    }

    public function addField(FormField $field): void
    {
        $this->fields->add($field);
    }

    public function removeField(FormField $field): void
    {
        $this->fields->removeElement($field);
    }

    /**
     * @return Collection<int, FormStatus>
     */
    public function getStatuses(): Collection
    {
        return $this->statuses;
    }

    public function addStatus(FormStatus $status): void
    {
        $this->statuses->add($status);
    }

    public function removeStatus(FormStatus $status): void
    {
        $this->statuses->removeElement($status);
    }

    public function getACLPermissions(): array
    {
        return ['create_submissions', 'view_submissions', 'manage_submissions', 'supervisor_manage_submissions'];
    }

    public function getACLParameters(): ACLParameters
    {
        return new ACLParameters(self::class, (string)$this->getId(), 'milhq_admin_form_list');
    }

    public function getIdentifierForAudit(): string
    {
        return (string)$this->getId();
    }

    public function getNameForAudit(): string
    {
        return $this->getName();
    }
}
