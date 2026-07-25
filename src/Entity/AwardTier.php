<?php

declare(strict_types=1);

namespace Forumify\Milhq\Entity;

use Doctrine\ORM\Mapping as ORM;
use Forumify\Core\Entity\AuditableEntityInterface;
use Forumify\Core\Entity\IdentifiableEntityTrait;
use Forumify\Core\Entity\SortableEntityInterface;
use Forumify\Core\Entity\SortableEntityTrait;
use Forumify\Core\Entity\TimestampableEntityTrait;
use Forumify\Milhq\Repository\AwardTierRepository;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(AwardTierRepository::class)]
#[ORM\Table('milhq_award_tier')]
class AwardTier implements SortableEntityInterface, AuditableEntityInterface
{
    use IdentifiableEntityTrait;
    use SortableEntityTrait;
    use TimestampableEntityTrait;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(allowNull: false)]
    public string $name;

    #[ORM\ManyToOne(inversedBy: 'tiers')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    public Award $parent;

    #[ORM\Column(length: 255, nullable: true)]
    public ?string $image = null;

    public function getIdentifierForAudit(): string
    {
        return (string)$this->getId();
    }

    public function getNameForAudit(): string
    {
        return $this->name;
    }
}
