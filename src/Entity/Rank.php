<?php

declare(strict_types=1);

namespace Forumify\Milhq\Entity;

use Doctrine\ORM\Mapping as ORM;
use Forumify\Core\Entity\AuditableEntityInterface;
use Forumify\Core\Entity\IdentifiableEntityTrait;
use Forumify\Core\Entity\SortableEntityTrait;
use Forumify\Core\Entity\TimestampableEntityTrait;
use Forumify\Milhq\Repository\RankRepository;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: RankRepository::class)]
#[ORM\Table('milhq_rank')]
class Rank implements GroupedEntityInterface, AuditableEntityInterface
{
    use IdentifiableEntityTrait;
    use SortableEntityTrait;
    use TimestampableEntityTrait;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(allowNull: false)]
    private string $name;

    #[ORM\Column(type: 'text')]
    private string $description = '';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(length: 8, nullable: true)]
    #[Assert\Length(max: 8)]
    private ?string $abbreviation = null;

    #[ORM\Column(length: 16, nullable: true)]
    #[Assert\Length(max: 16)]
    private ?string $paygrade = null;

    #[ORM\ManyToOne(targetEntity: RankGroup::class, inversedBy: 'ranks')]
    #[ORM\JoinColumn(name: 'group_id', onDelete: 'SET NULL')]
    private ?RankGroup $group = null;

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

    public function getAbbreviation(): ?string
    {
        return $this->abbreviation;
    }

    public function setAbbreviation(?string $abbreviation): void
    {
        $this->abbreviation = $abbreviation;
    }

    public function getPaygrade(): ?string
    {
        return $this->paygrade;
    }

    public function setPaygrade(?string $paygrade): void
    {
        $this->paygrade = $paygrade;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): void
    {
        $this->image = $image;
    }

    public function getGroup(): ?RankGroup
    {
        return $this->group;
    }

    public function setGroup(?RankGroup $group): void
    {
        $this->group = $group;
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
