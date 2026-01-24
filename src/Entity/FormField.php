<?php

declare(strict_types=1);

namespace Forumify\Milhq\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Forumify\Core\Entity\IdentifiableEntityTrait;
use Forumify\Core\Entity\SortableEntityInterface;
use Forumify\Core\Entity\SortableEntityTrait;
use Forumify\Core\Entity\TimestampableEntityTrait;
use Forumify\Milhq\Repository\FormFieldRepository;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: FormFieldRepository::class)]
#[ORM\Table('milhq_form_field')]
#[ORM\UniqueConstraint(fields: ['form', 'key'])]
#[UniqueEntity(fields: ['form', 'key'], message: 'This key is already used in this form.', errorPath: 'key')]
class FormField implements SortableEntityInterface
{
    use IdentifiableEntityTrait;
    use SortableEntityTrait;
    use TimestampableEntityTrait;

    #[ORM\ManyToOne(targetEntity: Form::class, inversedBy: 'fields')]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    private Form $form;

    #[ORM\Column('`key`', length: 255)]
    private string $key = '';

    #[ORM\Column(length: 64)]
    private string $type;

    #[ORM\Column(length: 255)]
    private string $label;

    #[ORM\Column(type: Types::TEXT)]
    private string $help = '';

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $required = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $readonly = false;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $options = null;

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

    public function isReadonly(): bool
    {
        return $this->readonly;
    }

    public function setReadonly(bool $readonly): void
    {
        $this->readonly = $readonly;
    }

    public function getOptions(): ?array
    {
        return $this->options;
    }

    public function setOptions(?array $options): void
    {
        $this->options = $options;
    }
}
