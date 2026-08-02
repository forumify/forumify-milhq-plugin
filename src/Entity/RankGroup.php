<?php

declare(strict_types=1);

namespace Forumify\Milhq\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Forumify\Milhq\Repository\RankGroupRepository;

#[ORM\Entity(repositoryClass: RankGroupRepository::class)]
#[ORM\Table('milhq_rank_group')]
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
