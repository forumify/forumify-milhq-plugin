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
use ApiPlatform\State\CreateProvider;
use Doctrine\ORM\Mapping as ORM;
use Forumify\Api\Entity\NewAsset;
use Forumify\Api\Serializer\Attribute\Asset;
use Forumify\Core\Entity\AuditableEntityInterface;
use Forumify\Core\Entity\IdentifiableEntityTrait;
use Forumify\Core\Entity\SortableEntityInterface;
use Forumify\Core\Entity\SortableEntityTrait;
use Forumify\Core\Entity\TimestampableEntityTrait;
use Forumify\Milhq\Repository\AwardTierRepository;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(AwardTierRepository::class)]
#[ORM\Table('milhq_award_tier')]
#[ApiResource(
    routePrefix: '/milhq',
    operations: [
        new Get(),
        new Patch(security: 'is_granted("milhq.admin.organization.awards.manage")'),
        new Delete(security: 'is_granted("milhq.admin.organization.awards.manage")'),
        new GetCollection(
            uriTemplate: '/awards/{parentId}/tiers',
            uriVariables: [
                'parentId' => new Link(fromClass: Award::class, toProperty: 'parent'),
            ],
        ),
        new Post(
            uriTemplate: '/awards/{parentId}/tiers',
            uriVariables: [
                'parentId' => new Link(fromClass: Award::class, toProperty: 'parent'),
            ],
            security: 'is_granted("milhq.admin.organization.awards.manage")',
            provider: CreateProvider::class,
        ),
    ],
)]
class AwardTier implements SortableEntityInterface, AuditableEntityInterface
{
    use IdentifiableEntityTrait;
    use SortableEntityTrait;
    use TimestampableEntityTrait;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(allowNull: false)]
    #[Groups(['AwardTier', 'Award::read', 'MilhqSummary'])]
    public string $name;

    #[ORM\ManyToOne(inversedBy: 'tiers')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups('AwardTier::read')]
    public Award $parent;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['AwardTier', 'Award::read', 'MilhqSummary'])]
    public ?string $image = null;

    #[Asset('image', 'milhq.asset', 'milhq_asset.storage')]
    public ?NewAsset $newImage = null;

    public function getIdentifierForAudit(): string
    {
        return (string)$this->getId();
    }

    public function getNameForAudit(): string
    {
        return $this->name;
    }
}
