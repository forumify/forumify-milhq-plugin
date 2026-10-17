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
use Forumify\Milhq\Repository\RankGroupRepository;

#[ORM\Entity(repositoryClass: RankGroupRepository::class)]
#[ORM\Table('milhq_rank_group')]
#[ApiResource(
    routePrefix: '/milhq',
    operations: [
        new Get(),
        new GetCollection(),
        new Post(security: 'is_granted("milhq.admin.organization.ranks.create")'),
        new Patch(security: 'is_granted("milhq.admin.organization.ranks.manage")'),
        new Delete(security: 'is_granted("milhq.admin.organization.ranks.delete")'),
    ],
)]
class RankGroup implements GroupInterface
{
    use GroupEntityTrait;

    /** @var Collection<int, Rank> */
    #[ORM\OneToMany(targetEntity: Rank::class, mappedBy: 'group')]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $ranks;

    public function __construct()
    {
        $this->ranks = new ArrayCollection();
    }

    /**
     * @return Collection<int, Rank>
     */
    public function getRanks(): Collection
    {
        return $this->ranks;
    }

    /**
     * @param Collection<int, Rank> $ranks
     */
    public function setRanks(Collection $ranks): void
    {
        $this->ranks = $ranks;
    }
}
