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
use Forumify\Core\Entity\SortableEntityInterface;
use Forumify\Core\Entity\SortableEntityTrait;
use Forumify\Core\Entity\TimestampableEntityTrait;
use Forumify\Milhq\Api\Processor\FormFieldProcessor;
use Forumify\Milhq\Form\SubmissionFormType;
use Forumify\Milhq\Repository\FormFieldRepository;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: FormFieldRepository::class)]
#[ORM\Table('milhq_form_field')]
#[ORM\UniqueConstraint(fields: ['form', 'key'])]
#[UniqueEntity(fields: ['form', 'key'], message: 'This key is already used in this form.', errorPath: 'label')]
#[ApiResource(
    routePrefix: '/milhq',
    operations: [
        new Get(extraProperties: ['acl' => ['permission' => 'create_submissions', 'entity' => 'form']]),
        new Patch(
            security: 'is_granted("milhq.admin.organization.forms.manage")',
            processor: FormFieldProcessor::class,
        ),
        new Delete(security: 'is_granted("milhq.admin.organization.forms.manage")'),
        new GetCollection(
            uriTemplate: '/forms/{formId}/fields',
            uriVariables: [
                'formId' => new Link(fromClass: Form::class, toProperty: 'form'),
            ],
            extraProperties: ['acl' => ['permission' => 'create_submissions', 'entity' => 'form']],
        ),
        new Post(
            uriTemplate: '/forms/{formId}/fields',
            uriVariables: [
                'formId' => new Link(fromClass: Form::class, toProperty: 'form'),
            ],
            security: 'is_granted("milhq.admin.organization.forms.manage")',
            provider: CreateProvider::class,
            processor: FormFieldProcessor::class,
        ),
    ],
)]
class FormField implements SortableEntityInterface, AuditableEntityInterface
{
    use IdentifiableEntityTrait;
    use SortableEntityTrait;
    use TimestampableEntityTrait;

    #[ORM\ManyToOne(targetEntity: Form::class, inversedBy: 'fields')]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[Groups('FormField::read')]
    private Form $form;

    #[ORM\Column('`key`', length: 255)]
    #[Groups(['FormField', 'Form::read'])]
    private string $key = '';

    #[ORM\Column(length: 64)]
    #[Assert\Choice(callback: [SubmissionFormType::class, 'getFieldTypes'])]
    #[Groups(['FormField', 'Form::read'])]
    private string $type;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(allowNull: false)]
    #[Groups(['FormField', 'Form::read'])]
    private string $label;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['FormField', 'Form::read'])]
    private string $help = '';

    #[ORM\Column(type: Types::BOOLEAN)]
    #[Groups(['FormField', 'Form::read'])]
    private bool $required = false;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['FormField', 'Form::read'])]
    public ?array $fieldOptions = null;

    public function getForm(): Form
    {
        return $this->form;
    }

    public function setForm(Form $form): void
    {
        $this->form = $form;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function setKey(string $key): void
    {
        $this->key = $key;
    }

    public function generateKeyIfEmpty(): void
    {
        if (!empty($this->key)) {
            return;
        }

        $this->key = new AsciiSlugger()
            ->slug($this->label)
            ->slice(0, 64)
            ->lower()
            ->toString()
        ;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): void
    {
        $this->label = $label;
    }

    public function getHelp(): string
    {
        return $this->help;
    }

    public function setHelp(string $help): void
    {
        $this->help = $help;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function setRequired(bool $required): void
    {
        $this->required = $required;
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
