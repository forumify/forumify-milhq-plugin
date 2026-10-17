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
use Forumify\Core\Entity\AuditableEntityInterface;
use Forumify\Core\Entity\IdentifiableEntityTrait;
use Forumify\Core\Entity\Role;
use Forumify\Core\Entity\SortableEntityInterface;
use Forumify\Core\Entity\SortableEntityTrait;
use Forumify\Core\Entity\TimestampableEntityTrait;
use Forumify\Milhq\Repository\PositionRepository;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: PositionRepository::class)]
#[ORM\Table('milhq_position')]
#[ApiResource(
    routePrefix: '/milhq',
    operations: [
        new Get(),
        new GetCollection(),
        new Post(security: 'is_granted("milhq.admin.organization.positions.create")'),
        new Patch(security: 'is_granted("milhq.admin.organization.positions.manage")'),
        new Delete(security: 'is_granted("milhq.admin.organization.positions.delete")'),
    ],
)]
class Position implements SortableEntityInterface, AuditableEntityInterface
{
    use IdentifiableEntityTrait;
    use SortableEntityTrait;
    use TimestampableEntityTrait;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(allowNull: false)]
    #[Groups(['Position', 'MilhqSummary'])]
    private string $name;

    #[ORM\Column(type: 'text')]
    #[Groups('Position')]
    private string $description = '';

    #[ORM\ManyToOne(targetEntity: Role::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    public ?Role $role = null;

    /** @var Collection<int, Equipment> */
    #[ORM\ManyToMany(targetEntity: Equipment::class)]
    #[ORM\JoinTable('milhq_position_primary_weapons')]
    #[Groups('Position')]
    private Collection $primaryWeapons;

    /** @var Collection<int, Equipment> */
    #[ORM\ManyToMany(targetEntity: Equipment::class)]
    #[ORM\JoinTable('milhq_position_secondary_weapons')]
    #[Groups('Position')]
    private Collection $secondaryWeapons;

    public function __construct()
    {
        $this->primaryWeapons = new ArrayCollection();
        $this->secondaryWeapons = new ArrayCollection();
    }

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

    /**
     * @return Collection<int, Equipment>
     */
    public function getPrimaryWeapons(): Collection
    {
        return $this->primaryWeapons;
    }

    /**
     * @param Collection<int, Equipment> $primaryWeapons
     */
    public function setPrimaryWeapons(Collection $primaryWeapons): void
    {
        $this->primaryWeapons = $primaryWeapons;
    }

    /**
     * @return Collection<int, Equipment>
     */
    public function getSecondaryWeapons(): Collection
    {
        return $this->secondaryWeapons;
    }

    /**
     * @param Collection<int, Equipment> $secondaryWeapons
     */
    public function setSecondaryWeapons(Collection $secondaryWeapons): void
    {
        $this->secondaryWeapons = $secondaryWeapons;
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
