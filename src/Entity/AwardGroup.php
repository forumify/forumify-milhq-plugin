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
use Forumify\Core\Entity\AccessControlledEntityInterface;
use Forumify\Core\Entity\ACLParameters;
use Forumify\Milhq\Repository\AwardGroupRepository;

#[ORM\Entity(repositoryClass: AwardGroupRepository::class)]
#[ORM\Table('milhq_award_group')]
#[ApiResource(
    routePrefix: '/milhq',
    operations: [
        new Get(),
        new GetCollection(),
        new Post(security: 'is_granted("milhq.admin.organization.awards.create")'),
        new Patch(security: 'is_granted("milhq.admin.organization.awards.manage")'),
        new Delete(security: 'is_granted("milhq.admin.organization.awards.delete")'),
    ],
)]
class AwardGroup implements GroupInterface, AccessControlledEntityInterface
{
    use GroupEntityTrait;

    /** @var Collection<int, Award> */
    #[ORM\OneToMany(targetEntity: Award::class, mappedBy: 'group')]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $awards;

    public function __construct()
    {
        $this->awards = new ArrayCollection();
    }

    /**
     * @return Collection<int, Award>
     */
    public function getAwards(): Collection
    {
        return $this->awards;
    }

    /**
     * @param Collection<int, Award> $awards
     */
    public function setAwards(Collection $awards): void
    {
        $this->awards = $awards;
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
            'milhq_admin_award_list',
        );
    }
}
