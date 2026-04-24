<?php

declare(strict_types=1);

namespace Forumify\Milhq\Entity;

use Doctrine\ORM\Mapping as ORM;
use Forumify\Core\Entity\IdentifiableEntityTrait;
use Forumify\Core\Entity\Role;
use Forumify\Core\Entity\SortableEntityInterface;
use Forumify\Core\Entity\SortableEntityTrait;
use Forumify\Core\Entity\TimestampableEntityTrait;
use Forumify\Milhq\Repository\PositionRepository;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: PositionRepository::class)]
#[ORM\Table('milhq_position')]
class Position implements SortableEntityInterface
{
    use IdentifiableEntityTrait;
    use SortableEntityTrait;
    use TimestampableEntityTrait;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(allowNull: false)]
    private string $name;

    #[ORM\Column(type: 'text')]
    private string $description = '';

    #[ORM\ManyToOne(targetEntity: Role::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    public ?Role $role = null;

    #[ORM\ManyToMany(targetEntity: Equipment::class)]
    #[ORM\JoinTable('milhq_position_primary_weapons')]
    private Collection $primaryWeapons;

    #[ORM\ManyToMany(targetEntity: Equipment::class)]
    #[ORM\JoinTable('milhq_position_secondary_weapons')]
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

    public function getSecondaryWeapons(): Collection
    {
        return $this->secondaryWeapons;
    }

    public function setSecondaryWeapons(Collection $secondaryWeapons): void
    {
        $this->secondaryWeapons = $secondaryWeapons;
    }

    public function getPrimaryWeapons(): Collection
    {
        return $this->primaryWeapons;
    }

    public function setPrimaryWeapons(Collection $primaryWeapons): void
    {
        $this->primaryWeapons = $primaryWeapons;
    }
}
