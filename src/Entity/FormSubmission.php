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
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Forumify\Core\Entity\AuditableEntityInterface;
use Forumify\Core\Entity\IdentifiableEntityTrait;
use Forumify\Core\Entity\TimestampableEntityTrait;
use Forumify\Milhq\Api\Processor\FormSubmissionCreateProcessor;
use Forumify\Milhq\Api\Processor\FormSubmissionStatusProcessor;
use Forumify\Milhq\Repository\FormSubmissionRepository;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: FormSubmissionRepository::class)]
#[ORM\Table('milhq_form_submission')]
#[ApiResource(
    routePrefix: '/milhq',
    normalizationContext: ['groups' => ['MilhqSummary']],
    order: ['createdAt' => 'DESC'],
    operations: [
        new Get(security: 'object.getSoldier().getUser()?.getId() == user.getUserId() or is_granted("ACCESS_CONTROL_LIST", {"permission": "view_submissions", "entity": object.getForm()})'),
        new GetCollection(extraProperties: ['acl' => ['permission' => 'view_submissions', 'entity' => 'form']]),
        new Patch(
            normalizationContext: ['groups' => ['FormSubmission::read', 'MilhqSummary']],
            denormalizationContext: ['groups' => ['FormSubmission::status']],
            extraProperties: ['acl' => ['permission' => 'manage_submissions', 'entity' => 'form']],
            processor: FormSubmissionStatusProcessor::class,
        ),
        new Delete(security: 'is_granted("milhq.admin.submissions.delete")'),
        new GetCollection(
            uriTemplate: '/forms/{formId}/submissions',
            uriVariables: [
                'formId' => new Link(fromClass: Form::class, toProperty: 'form'),
            ],
            extraProperties: ['acl' => ['permission' => 'view_submissions', 'entity' => 'form']],
        ),
        new Post(
            uriTemplate: '/forms/{formId}/submissions',
            uriVariables: [
                'formId' => new Link(
                    fromClass: Form::class,
                    toProperty: 'form',
                    security: 'is_granted("ACCESS_CONTROL_LIST", {"permission": "create_submissions", "entity": form})',
                ),
            ],
            normalizationContext: ['groups' => ['FormSubmission::read', 'MilhqSummary']],
            denormalizationContext: ['groups' => ['FormSubmission::create']],
            provider: CreateProvider::class,
            processor: FormSubmissionCreateProcessor::class,
        ),
    ],
)]
class FormSubmission implements AuditableEntityInterface
{
    use IdentifiableEntityTrait;
    use TimestampableEntityTrait;

    #[ORM\ManyToOne(targetEntity: Form::class, inversedBy: 'submissions')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups('FormSubmission::read')]
    private Form $form;

    #[ORM\ManyToOne(targetEntity: Soldier::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups('FormSubmission::read')]
    private Soldier $soldier;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['FormSubmission::read', 'FormSubmission::create'])]
    private ?array $data = [];

    #[ORM\ManyToOne(targetEntity: FormStatus::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    #[Groups(['FormSubmission::read', 'FormSubmission::status'])]
    private ?FormStatus $status = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['FormSubmission::read', 'FormSubmission::status'])]
    private ?string $statusReason = null;

    #[Groups('FormSubmission::status')]
    public bool $sendNotification = false;

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
