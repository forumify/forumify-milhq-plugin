<?php

declare(strict_types=1);

namespace Forumify\Milhq\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\JoinColumn;
use Forumify\Core\Entity\IdentifiableEntityTrait;
use Forumify\Core\Entity\Role;
use Forumify\Core\Entity\SortableEntityInterface;
use Forumify\Core\Entity\SortableEntityTrait;
use Forumify\Core\Entity\TimestampableEntityTrait;
use Forumify\Milhq\Repository\UnitRepository;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UnitRepository::class)]
#[ORM\Table('milhq_unit')]
class Unit implements SortableEntityInterface
{
    use IdentifiableEntityTrait;
    use SortableEntityTrait;
    use TimestampableEntityTrait;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(allowNull: false)]
    private string $name;

    #[ORM\Column(type: 'text')]
    private string $description = '';

    #[ORM\OneToMany(mappedBy: 'unit', targetEntity: Soldier::class)]
    private Collection $soldiers;

    #[ORM\ManyToMany(targetEntity: Roster::class, mappedBy: 'units')]
    private Collection $rosters;

    /**
     * @var Collection<int, Position>
     */
    #[ORM\ManyToMany(targetEntity: Position::class)]
    #[ORM\JoinTable(
        name: 'milhq_unit_supervisors',
        joinColumns: new JoinColumn(onDelete: 'CASCADE'),
        inverseJoinColumns: new JoinColumn(onDelete: 'CASCADE'),
    )]
    public Collection $supervisors;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => 1])]
    public bool $markSupervisorsOnRoster = true;

    #[ORM\ManyToOne(targetEntity: Role::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    public ?Role $role = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $designation = null;

    public function __construct()
    {
        $this->soldiers = new ArrayCollection();
        $this->rosters = new ArrayCollection();
        $this->supervisors = new ArrayCollection();
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
     * @return Collection<int, Soldier>
     */
    public function getSoldiers(): Collection
    {
        return $this->soldiers;
    }

    /**
     * @param Collection<int, Soldier> $users
     */
    public function setSoldiers(Collection $users): void
    {
        $this->soldiers = $users;
    }

    public function getRosters(): Collection
    {
        return $this->rosters;
    }

    public function setRosters(Collection $rosters): void
    {
        $this->rosters = $rosters;
    }

    public function getDesignation(): ?string
    {
        return $this->designation;
    }

    public function setDesignation(?string $designation): void
    {
        $this->designation = $designation;
    }
}
