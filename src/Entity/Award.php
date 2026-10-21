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
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Forumify\Api\Entity\NewAsset;
use Forumify\Api\Serializer\Attribute\Asset;
use Forumify\Core\Entity\AuditableEntityInterface;
use Forumify\Core\Entity\IdentifiableEntityTrait;
use Forumify\Core\Entity\SortableEntityTrait;
use Forumify\Core\Entity\TimestampableEntityTrait;
use Forumify\Milhq\Api\Processor\GroupedEntityProcessor;
use Forumify\Milhq\Repository\AwardRepository;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AwardRepository::class)]
#[ORM\Table('milhq_award')]
#[ApiResource(
    routePrefix: '/milhq',
    operations: [
        new Get(),
        new GetCollection(),
        new Post(security: 'is_granted("milhq.admin.organization.awards.create")'),
        new Patch(
            security: 'is_granted("milhq.admin.organization.awards.manage")',
            processor: GroupedEntityProcessor::class,
        ),
        new Delete(security: 'is_granted("milhq.admin.organization.awards.delete")'),
        new GetCollection(
            uriTemplate: '/award-groups/{groupId}/awards',
            uriVariables: [
                'groupId' => new Link(fromClass: AwardGroup::class, toProperty: 'group'),
            ],
        ),
    ],
)]
class Award implements GroupedEntityInterface, AuditableEntityInterface
{
    use IdentifiableEntityTrait;
    use SortableEntityTrait;
    use TimestampableEntityTrait;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(allowNull: false)]
    #[Groups(['Award', 'MilhqSummary'])]
    private string $name;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups('Award')]
    private string $description = '';

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['Award', 'MilhqSummary'])]
    public ?string $image = null;

    #[Asset('image', 'milhq.asset', 'milhq_asset.storage')]
    public ?NewAsset $newImage = null;

    /** @var Collection<int, AwardTier> */
    #[ORM\OneToMany(
        targetEntity: AwardTier::class,
        mappedBy: 'parent',
        cascade: ['persist', 'remove'],
        fetch: 'EAGER',
        orphanRemoval: true,
    )]
    #[ORM\OrderBy(['position' => 'DESC'])]
    #[Groups('Award::read')]
    public Collection $tiers;

    #[ORM\Column(type: Types::BOOLEAN)]
    #[Groups('Award')]
    public bool $autoAdvanceTiers = false;

    #[ORM\ManyToOne(targetEntity: AwardGroup::class, inversedBy: 'awards')]
    #[ORM\JoinColumn(name: 'group_id', onDelete: 'SET NULL')]
    #[Groups('Award')]
    private ?AwardGroup $group = null;

    public function __construct()
    {
        $this->tiers = new ArrayCollection();
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

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): void
    {
        $this->image = $image;
    }

    public function getGroup(): ?AwardGroup
    {
        return $this->group;
    }

    public function setGroup(?AwardGroup $group): void
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

    public function addTier(AwardTier $tier): void
    {
        $tier->parent = $this;
        $this->tiers->add($tier);
    }

    public function removeTier(AwardTier $tier): void
    {
        $this->tiers->removeElement($tier);
    }
}
