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
use Forumify\Api\Entity\NewAsset;
use Forumify\Api\Serializer\Attribute\Asset;
use Forumify\Core\Entity\AccessControlledEntityInterface;
use Forumify\Core\Entity\ACLParameters;
use Forumify\Core\Entity\AuditableEntityInterface;
use Forumify\Core\Entity\IdentifiableEntityTrait;
use Forumify\Core\Entity\SluggableEntityTrait;
use Forumify\Core\Entity\SortableEntityInterface;
use Forumify\Core\Entity\SortableEntityTrait;
use Forumify\Milhq\Repository\CourseRepository;
use Forumify\Plugin\Attribute\PluginVersion;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[PluginVersion('forumify/forumify-milhq-plugin', 'premium')]
#[ORM\Entity(repositoryClass: CourseRepository::class)]
#[ORM\Table(name: 'milhq_course')]
#[ApiResource(
    routePrefix: '/milhq',
    normalizationContext: ['groups' => ['MilhqSummary']],
    operations: [
        new Get(extraProperties: ['acl' => 'view']),
        new GetCollection(extraProperties: ['acl' => 'view']),
        new Post(security: 'is_granted("milhq.admin.courses.manage")'),
        new Patch(security: 'is_granted("milhq.admin.courses.manage")'),
        new Delete(security: 'is_granted("milhq.admin.courses.delete")'),
    ],
)]
class Course implements AccessControlledEntityInterface, SortableEntityInterface, AuditableEntityInterface
{
    use IdentifiableEntityTrait;
    use SluggableEntityTrait;
    use SortableEntityTrait;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(allowNull: false)]
    #[Groups(['Course', 'MilhqSummary'])]
    private string $title;

    #[ORM\Column(type: 'text')]
    #[Groups('Course')]
    private string $description = '';

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['Course', 'MilhqSummary'])]
    private ?string $image = null;

    #[Asset('image', 'milhq.asset', 'milhq_asset.storage')]
    public ?NewAsset $newImage = null;

    #[ORM\ManyToOne(targetEntity: Rank::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    #[Groups('Course')]
    private ?Rank $minimumRank = null;

    /**
     * @var array<string>|null
     */
    #[ORM\Column(type: 'simple_array', nullable: true)]
    #[Groups('Course')]
    private ?array $prerequisites = [];

    /**
     * @var array<string>|null
     */
    #[ORM\Column(type: 'simple_array', nullable: true)]
    #[Groups('Course')]
    private ?array $qualifications = [];

    /**
     * @var array<string>|null
     */
    #[ORM\Column(type: 'simple_array', nullable: true)]
    #[Groups('Course')]
    private ?array $awards = [];

    /** @var Collection<int, CourseClass> */
    #[ORM\OneToMany(mappedBy: 'course', targetEntity: CourseClass::class, cascade: ['persist', 'remove'])]
    private Collection $classes;

    /** @var Collection<int, CourseInstructor> */
    #[ORM\OneToMany(mappedBy: 'course', targetEntity: CourseInstructor::class, cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['position' => 'ASC'])]
    #[Groups('Course::read')]
    private Collection $instructors;

    public function __construct()
    {
        $this->classes = new ArrayCollection();
        $this->instructors = new ArrayCollection();
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
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

    public function getMinimumRank(): ?Rank
    {
        return $this->minimumRank;
    }

    public function setMinimumRank(?Rank $minimumRank): void
    {
        $this->minimumRank = $minimumRank;
    }

    /**
     * @return array<int>
     */
    public function getPrerequisites(): array
    {
        return array_map(fn (mixed $id) => (int)$id, $this->prerequisites ?? []);
    }

    /**
     * @param array<string|int>|null $prerequisites
     */
    public function setPrerequisites(?array $prerequisites): void
    {
        $this->prerequisites = $prerequisites === null ? null : array_map(fn (int|string $id) => (string)$id, $prerequisites);
    }

    /**
     * @return array<int>
     */
    public function getQualifications(): array
    {
        return array_map(fn (mixed $id) => (int)$id, $this->qualifications ?? []);
    }

    /**
     * @param array<string|int>|null $qualifications
     */
    public function setQualifications(?array $qualifications): void
    {
        $this->qualifications = $qualifications === null ? null : array_map(fn (int|string $id) => (string)$id, $qualifications);
    }

    /**
     * @return array<int>
     */
    public function getAwards(): array
    {
        return array_map(fn (mixed $id) => (int)$id, $this->awards ?? []);
    }

    /**
     * @param array<string|int>|null $awards
     */
    public function setAwards(?array $awards): void
    {
        $this->awards = $awards === null ? null : array_map(fn (int|string $id) => (string)$id, $awards);
    }

    /**
     * @return Collection<int, CourseClass>
     */
    public function getClasses(): Collection
    {
        return $this->classes;
    }

    /**
     * @param Collection<int, CourseClass> $classes
     */
    public function setClasses(Collection $classes): void
    {
        $this->classes = $classes;
    }

    /**
     * @return Collection<int, CourseInstructor>
     */
    public function getInstructors(): Collection
    {
        return $this->instructors;
    }

    /**
     * @param Collection<int, CourseInstructor> $instructors
     */
    public function setInstructors(Collection $instructors): void
    {
        $this->instructors = $instructors;
    }

    public function getACLPermissions(): array
    {
        return [
            'view',
            'view_classes',
            'manage_classes',
            'signup_as_instructor',
        ];
    }

    public function getACLParameters(): ACLParameters
    {
        return new ACLParameters(
            self::class,
            (string)$this->getId(),
            'milhq_admin_courses_list',
            ['id' => $this->getId()],
        );
    }

    public function getIdentifierForAudit(): string
    {
        return (string)$this->getId();
    }

    public function getNameForAudit(): string
    {
        return $this->getTitle();
    }
}
