<?php

declare(strict_types=1);

namespace Forumify\Milhq\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Forumify\Milhq\Repository\AwardGroupRepository;

#[ORM\Entity(repositoryClass: AwardGroupRepository::class)]
#[ORM\Table('milhq_award_group')]
class AwardGroup implements GroupInterface
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
}
