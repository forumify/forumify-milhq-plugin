<?php

declare(strict_types=1);

namespace Forumify\Milhq\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use Doctrine\ORM\Mapping as ORM;
use Forumify\Api\Entity\NewAsset;
use Forumify\Api\Serializer\Attribute\Asset;
use Forumify\Core\Entity\AuditableEntityInterface;
use Forumify\Core\Entity\IdentifiableEntityTrait;
use Forumify\Core\Entity\Role;
use Forumify\Core\Entity\SortableEntityInterface;
use Forumify\Core\Entity\SortableEntityTrait;
use Forumify\Core\Entity\TimestampableEntityTrait;
use Forumify\Milhq\Repository\SpecialtyRepository;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SpecialtyRepository::class)]
#[ORM\Table('milhq_specialty')]
#[ApiResource(
    routePrefix: '/milhq',
    operations: [
        new Get(),
        new GetCollection(),
        new Post(security: 'is_granted("milhq.admin.organization.specialties.create")'),
        new Patch(security: 'is_granted("milhq.admin.organization.specialties.manage")'),
        new Delete(security: 'is_granted("milhq.admin.organization.specialties.delete")'),
    ],
)]
class Specialty implements SortableEntityInterface, AuditableEntityInterface
{
    use IdentifiableEntityTrait;
    use SortableEntityTrait;
    use TimestampableEntityTrait;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(allowNull: false)]
    #[Groups(['Specialty', 'MilhqSummary'])]
    private string $name;

    #[ORM\Column(type: 'text')]
    #[Groups('Specialty')]
    private string $description = '';

    #[ORM\Column(length: 8)]
    #[Assert\Length(max: 8)]
    #[Assert\NotBlank(allowNull: false)]
    #[Groups(['Specialty', 'MilhqSummary'])]
    private string $abbreviation = '';

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['Specialty', 'MilhqSummary'])]
    private ?string $image = null;

    #[Asset('image', 'milhq.asset', 'milhq_asset.storage')]
    public ?NewAsset $newImage = null;

    #[ORM\ManyToOne(targetEntity: Role::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    public ?Role $role = null;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getAbbreviation(): string
    {
        return $this->abbreviation;
    }

    public function setAbbreviation(string $abbreviation): void
    {
        $this->abbreviation = $abbreviation;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): void
    {
        $this->image = $image;
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
