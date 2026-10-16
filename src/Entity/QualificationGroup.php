<?php

declare(strict_types=1);

namespace Forumify\Milhq\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Forumify\Milhq\Repository\QualificationGroupRepository;

#[ORM\Entity(repositoryClass: QualificationGroupRepository::class)]
#[ORM\Table('milhq_qualification_group')]
class QualificationGroup implements GroupInterface
{
    use GroupEntityTrait;

    /** @var Collection<int, Qualification> */
    #[ORM\OneToMany(targetEntity: Qualification::class, mappedBy: 'group')]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $qualifications;

    public function __construct()
    {
        $this->qualifications = new ArrayCollection();
    }

    /**
     * @return Collection<int, Qualification>
     */
    public function getQualifications(): Collection
    {
        return $this->qualifications;
    }

    /**
     * @param Collection<int, Qualification> $qualifications
     */
    public function setQualifications(Collection $qualifications): void
    {
        $this->qualifications = $qualifications;
    }
}
