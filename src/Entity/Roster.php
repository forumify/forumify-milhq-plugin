<?php

declare(strict_types=1);

namespace Forumify\Milhq\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\JoinColumn;
use Forumify\Core\Entity\AuditableEntityInterface;
use Forumify\Core\Entity\IdentifiableEntityTrait;
use Forumify\Core\Entity\SortableEntityInterface;
use Forumify\Core\Entity\SortableEntityTrait;
use Forumify\Core\Entity\TimestampableEntityTrait;
use Forumify\Milhq\Repository\RosterRepository;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: RosterRepository::class)]
#[ORM\Table('milhq_roster')]
#[ApiResource(
    routePrefix: '/milhq',
    operations: [
        new Get(),
        new GetCollection(),
        new Post(security: 'is_granted("milhq.admin.organization.rosters.create")'),
        new Patch(security: 'is_granted("milhq.admin.organization.rosters.manage")'),
        new Delete(security: 'is_granted("milhq.admin.organization.rosters.delete")'),
    ],
)]
class Roster implements SortableEntityInterface, AuditableEntityInterface
{
    use IdentifiableEntityTrait;
    use SortableEntityTrait;
    use TimestampableEntityTrait;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(allowNull: false)]
    #[Groups('Roster')]
    private string $name;

    #[ORM\Column(type: 'text')]
    #[Groups('Roster')]
    private string $description = '';

    /** @var Collection<int, Unit> */
    #[ORM\ManyToMany(targetEntity: Unit::class, inversedBy: 'rosters')]
    #[ORM\JoinTable(
        name: 'milhq_roster_units',
        joinColumns: new JoinColumn(nullable: false, onDelete: 'CASCADE'),
        inverseJoinColumns: new JoinColumn(nullable: false, onDelete: 'CASCADE')
    )]
    #[ORM\OrderBy(['position' => 'ASC'])]
    #[Groups('Roster')]
    private Collection $units;

    public function __construct()
    {
        $this->units = new ArrayCollection();
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
     * @return Collection<int, Unit>
     */
    public function getUnits(): Collection
    {
        return $this->units;
    }

    /**
     * @param Collection<int, Unit> $units
     */
    public function setUnits(Collection $units): void
    {
        $this->units = $units;
    }

    public function addUnit(Unit $unit): void
    {
        $this->units->add($unit);
    }

    public function removeUnit(Unit $unit): void
    {
        $this->units->removeElement($unit);
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
