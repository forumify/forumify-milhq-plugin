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
use Forumify\Milhq\Repository\QualificationTierRepository;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(QualificationTierRepository::class)]
#[ORM\Table('milhq_qualification_tier')]
#[ApiResource(
    routePrefix: '/milhq',
    operations: [
        new Get(),
        new Patch(security: 'is_granted("milhq.admin.organization.qualifications.manage")'),
        new Delete(security: 'is_granted("milhq.admin.organization.qualifications.manage")'),
        new GetCollection(
            uriTemplate: '/qualifications/{parentId}/tiers',
            uriVariables: [
                'parentId' => new Link(fromClass: Qualification::class, toProperty: 'parent'),
            ],
        ),
        new Post(
            uriTemplate: '/qualifications/{parentId}/tiers',
            uriVariables: [
                'parentId' => new Link(fromClass: Qualification::class, toProperty: 'parent'),
            ],
            security: 'is_granted("milhq.admin.organization.qualifications.manage")',
            provider: CreateProvider::class,
        ),
    ],
)]
class QualificationTier implements SortableEntityInterface, AuditableEntityInterface
{
    use IdentifiableEntityTrait;
    use SortableEntityTrait;
    use TimestampableEntityTrait;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(allowNull: false)]
    #[Groups(['QualificationTier', 'Qualification::read', 'MilhqSummary'])]
    public string $name;

    #[ORM\ManyToOne(inversedBy: 'tiers')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups('QualificationTier::read')]
    public Qualification $parent;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['QualificationTier', 'Qualification::read', 'MilhqSummary'])]
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
