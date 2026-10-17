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
use Doctrine\ORM\Mapping as ORM;
use Forumify\Api\Entity\NewAsset;
use Forumify\Api\Serializer\Attribute\Asset;
use Forumify\Core\Entity\AuditableEntityInterface;
use Forumify\Core\Entity\IdentifiableEntityTrait;
use Forumify\Core\Entity\SortableEntityTrait;
use Forumify\Core\Entity\TimestampableEntityTrait;
use Forumify\Milhq\Api\Processor\GroupedEntityProcessor;
use Forumify\Milhq\Repository\RankRepository;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: RankRepository::class)]
#[ORM\Table('milhq_rank')]
#[ApiResource(
    routePrefix: '/milhq',
    operations: [
        new Get(),
        new GetCollection(),
        new Post(security: 'is_granted("milhq.admin.organization.ranks.create")'),
        new Patch(
            security: 'is_granted("milhq.admin.organization.ranks.manage")',
            processor: GroupedEntityProcessor::class,
        ),
        new Delete(security: 'is_granted("milhq.admin.organization.ranks.delete")'),
        new GetCollection(
            uriTemplate: '/rank-groups/{groupId}/ranks',
            uriVariables: [
                'groupId' => new Link(fromClass: RankGroup::class, toProperty: 'group'),
            ],
        ),
    ],
)]
class Rank implements GroupedEntityInterface, AuditableEntityInterface
{
    use IdentifiableEntityTrait;
    use SortableEntityTrait;
    use TimestampableEntityTrait;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(allowNull: false)]
    #[Groups(['Rank', 'MilhqSummary'])]
    private string $name;

    #[ORM\Column(type: 'text')]
    #[Groups('Rank')]
    private string $description = '';

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['Rank', 'MilhqSummary'])]
    private ?string $image = null;

    #[Asset('image', 'milhq.asset', 'milhq_asset.storage')]
    public ?NewAsset $newImage = null;

    #[ORM\Column(length: 8)]
    #[Assert\Length(max: 8)]
    #[Groups(['Rank', 'MilhqSummary'])]
    private string $abbreviation = '';

    #[ORM\Column(length: 16)]
    #[Assert\Length(max: 16)]
    #[Groups(['Rank', 'MilhqSummary'])]
    private string $paygrade = '';

    #[ORM\ManyToOne(targetEntity: RankGroup::class, inversedBy: 'ranks')]
    #[ORM\JoinColumn(name: 'group_id', onDelete: 'SET NULL')]
    #[Groups('Rank')]
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

    public function getAbbreviation(): string
    {
        return $this->abbreviation;
    }

    public function setAbbreviation(string $abbreviation): void
    {
        $this->abbreviation = $abbreviation;
    }

    public function getPaygrade(): string
    {
        return $this->paygrade;
    }

    public function setPaygrade(string $paygrade): void
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
