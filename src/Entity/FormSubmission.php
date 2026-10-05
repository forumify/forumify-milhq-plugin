<?php

declare(strict_types=1);

namespace Forumify\Milhq\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Forumify\Core\Entity\AuditableEntityInterface;
use Forumify\Core\Entity\IdentifiableEntityTrait;
use Forumify\Core\Entity\TimestampableEntityTrait;
use Forumify\Milhq\Repository\FormSubmissionRepository;

#[ORM\Entity(repositoryClass: FormSubmissionRepository::class)]
#[ORM\Table('milhq_form_submission')]
class FormSubmission implements AuditableEntityInterface
{
    use IdentifiableEntityTrait;
    use TimestampableEntityTrait;

    #[ORM\ManyToOne(targetEntity: Form::class, inversedBy: 'submissions')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Form $form;

    #[ORM\ManyToOne(targetEntity: Soldier::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Soldier $soldier;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $data = [];

    #[ORM\ManyToOne(targetEntity: FormStatus::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?FormStatus $status = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $statusReason = null;

    public function getForm(): Form
    {
        return $this->form;
    }

    public function setForm(Form $form): void
    {
        $this->form = $form;
    }

    public function getSoldier(): Soldier
    {
        return $this->soldier;
    }

    public function setSoldier(Soldier $soldier): void
    {
        $this->soldier = $soldier;
    }

    public function getData(): ?array
    {
        return $this->data;
    }

    public function setData(?array $data): void
    {
        $this->data = $data;
    }

    public function getStatus(): ?FormStatus
    {
        return $this->status;
    }

    public function setStatus(?FormStatus $status): void
    {
        $this->status = $status;
    }

    public function getStatusReason(): ?string
    {
        return $this->statusReason;
    }

    public function setStatusReason(?string $statusReason): void
    {
        $this->statusReason = $statusReason;
    }

    public function getIdentifierForAudit(): string
    {
        return (string)$this->getId();
    }

    public function getNameForAudit(): string
    {
        return $this->getForm()->getName();
    }
}
