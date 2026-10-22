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
use Forumify\Milhq\Repository\QualificationGroupRepository;

#[ORM\Entity(repositoryClass: QualificationGroupRepository::class)]
#[ORM\Table('milhq_qualification_group')]
#[ApiResource(
    routePrefix: '/milhq',
    operations: [
        new Get(),
        new GetCollection(),
        new Post(security: 'is_granted("milhq.admin.organization.qualifications.create")'),
        new Patch(security: 'is_granted("milhq.admin.organization.qualifications.manage")'),
        new Delete(security: 'is_granted("milhq.admin.organization.qualifications.delete")'),
    ],
)]
class QualificationGroup implements GroupInterface, AccessControlledEntityInterface
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
}
