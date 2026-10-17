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
use Forumify\Core\Entity\IdentifiableEntityTrait;
use Forumify\Core\Entity\SortableEntityInterface;
use Forumify\Core\Entity\SortableEntityTrait;
use Forumify\Core\Entity\TimestampableEntityTrait;
use Forumify\Milhq\Repository\FormStatusRepository;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: FormStatusRepository::class)]
#[ORM\Table('milhq_form_status')]
#[ApiResource(
    routePrefix: '/milhq',
    operations: [
        new Get(extraProperties: ['acl' => ['permission' => 'create_submissions', 'entity' => 'form']]),
        new Patch(
            security: 'is_granted("milhq.admin.organization.forms.manage")',
        ),
        new Delete(security: 'is_granted("milhq.admin.organization.forms.manage")'),
        new GetCollection(
            uriTemplate: '/forms/{formId}/statuses',
            uriVariables: [
                'formId' => new Link(fromClass: Form::class, toProperty: 'form'),
            ],
            extraProperties: ['acl' => ['permission' => 'create_submissions', 'entity' => 'form']],
        ),
        new Post(
            uriTemplate: '/forms/{formId}/statuses',
            uriVariables: [
                'formId' => new Link(fromClass: Form::class, toProperty: 'form'),
            ],
            security: 'is_granted("milhq.admin.organization.forms.manage")',
            provider: CreateProvider::class,
        ),
    ],
)]
class FormStatus implements SortableEntityInterface, AuditableEntityInterface
{
    use IdentifiableEntityTrait;
    use SortableEntityTrait;
    use TimestampableEntityTrait;

    #[ORM\ManyToOne(targetEntity: Form::class, inversedBy: 'statuses')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups('FormStatus::read')]
    private Form $form;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(allowNull: false)]
    #[Groups(['FormStatus', 'Form::read', 'MilhqSummary'])]
    private string $name;

    #[ORM\Column(length: 7, options: ['fixed' => true])]
    #[Groups(['FormStatus', 'Form::read', 'MilhqSummary'])]
    private string $color = '';

    public function getForm(): Form
    {
        return $this->form;
    }

    public function setForm(Form $form): void
    {
        $this->form = $form;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getColor(): string
    {
        return $this->color;
    }

    public function setColor(string $color): void
    {
        $this->color = $color;
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
