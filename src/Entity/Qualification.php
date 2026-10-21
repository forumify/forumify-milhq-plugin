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
use Doctrine\ORM\Mapping as ORM;
use Forumify\Api\Entity\NewAsset;
use Forumify\Api\Serializer\Attribute\Asset;
use Forumify\Core\Entity\AccessControlledEntityInterface;
use Forumify\Core\Entity\ACLParameters;
use Forumify\Core\Entity\AuditableEntityInterface;
use Forumify\Core\Entity\IdentifiableEntityTrait;
use Forumify\Core\Entity\SortableEntityTrait;
use Forumify\Core\Entity\TimestampableEntityTrait;
use Forumify\Milhq\Api\Processor\GroupedEntityProcessor;
use Forumify\Milhq\Repository\QualificationRepository;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: QualificationRepository::class)]
#[ORM\Table('milhq_qualification')]
#[ApiResource(
    routePrefix: '/milhq',
    operations: [
        new Get(),
        new GetCollection(),
        new Post(security: 'is_granted("milhq.admin.organization.qualifications.create")'),
        new Patch(
            security: 'is_granted("milhq.admin.organization.qualifications.manage")',
            processor: GroupedEntityProcessor::class,
        ),
        new Delete(security: 'is_granted("milhq.admin.organization.qualifications.delete")'),
        new GetCollection(
            uriTemplate: '/qualification-groups/{groupId}/qualifications',
            uriVariables: [
                'groupId' => new Link(fromClass: QualificationGroup::class, toProperty: 'group'),
            ],
        ),
    ],
)]
class Qualification implements GroupedEntityInterface, AuditableEntityInterface, AccessControlledEntityInterface
{
    use IdentifiableEntityTrait;
    use SortableEntityTrait;
    use TimestampableEntityTrait;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(allowNull: false)]
    #[Groups(['Qualification', 'MilhqSummary'])]
    private string $name;

    #[ORM\Column(type: 'text')]
    #[Groups('Qualification')]
    private string $description = '';

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['Qualification', 'MilhqSummary'])]
    private ?string $image = null;

    #[Asset('image', 'milhq.asset', 'milhq_asset.storage')]
    public ?NewAsset $newImage = null;

    /** @var Collection<int, QualificationTier> */
    #[ORM\OneToMany(
        targetEntity: QualificationTier::class,
        mappedBy: 'parent',
        cascade: ['persist', 'remove'],
        fetch: 'EAGER',
        orphanRemoval: true,
    )]
    #[ORM\OrderBy(['position' => 'DESC'])]
    #[Groups('Qualification::read')]
    public Collection $tiers;

    #[ORM\ManyToOne(targetEntity: QualificationGroup::class, inversedBy: 'qualifications')]
    #[ORM\JoinColumn(name: 'group_id', onDelete: 'SET NULL')]
    #[Groups('Qualification')]
    private ?QualificationGroup $group = null;

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

    public function getGroup(): ?QualificationGroup
    {
        return $this->group;
    }

    public function setGroup(?QualificationGroup $group): void
    {
        $this->group = $group;
    }

    public function getACLPermissions(): array
    {
        return ['view'];
    }

    public function getACLParameters(): ACLParameters
    {
        return new ACLParameters(
            self::class,
            (string)$this->getId(),
            'milhq_admin_qualification_list',
        );
    }

    public function getIdentifierForAudit(): string
    {
        return (string)$this->getId();
    }

    public function getNameForAudit(): string
    {
        return $this->getName();
    }

    public function addTier(QualificationTier $tier): void
    {
        $tier->parent = $this;
        $this->tiers->add($tier);
    }

    public function removeTier(QualificationTier $tier): void
    {
        $this->tiers->removeElement($tier);
    }
}
