<?php

declare(strict_types=1);

namespace Forumify\Milhq\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\CreateProvider;
use Forumify\Milhq\Api\Processor\CourseClassStudentProcessor;
use Doctrine\ORM\Mapping as ORM;
use Forumify\Core\Entity\IdentifiableEntityTrait;
use Forumify\Plugin\Attribute\PluginVersion;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Forumify\Milhq\Entity\Enum\CourseResult;

#[PluginVersion('forumify/forumify-milhq-plugin', 'premium')]
#[ORM\Entity]
#[ORM\Table('milhq_course_class_student')]
#[UniqueEntity(['class', 'soldier'], errorPath: 'soldier')]
#[ApiResource(
    routePrefix: '/milhq',
    normalizationContext: ['groups' => ['MilhqSummary']],
    operations: [
        new Get(extraProperties: ['acl' => ['permission' => 'view_classes', 'entity' => 'class.course']]),
        new Patch(
            extraProperties: ['acl' => ['permission' => 'manage_classes', 'entity' => 'class.course']],
            processor: CourseClassStudentProcessor::class,
        ),
        new Delete(
            extraProperties: ['acl' => ['permission' => 'manage_classes', 'entity' => 'class.course']],
            processor: CourseClassStudentProcessor::class,
        ),
        new GetCollection(
            uriTemplate: '/course-classes/{classId}/students',
            uriVariables: [
                'classId' => new Link(fromClass: CourseClass::class, toProperty: 'class'),
            ],
            extraProperties: ['acl' => ['permission' => 'view_classes', 'entity' => 'class.course']],
        ),
        new Post(
            uriTemplate: '/course-classes/{classId}/students',
            uriVariables: [
                'classId' => new Link(
                    fromClass: CourseClass::class,
                    toProperty: 'class',
                    security: 'is_granted("MILHQ_COURSE_CLASS_MANAGE", courseClass)',
                    securityObjectName: 'courseClass',
                ),
            ],
            provider: CreateProvider::class,
            processor: CourseClassStudentProcessor::class,
        ),
    ],
)]
class CourseClassStudent
{
    use IdentifiableEntityTrait;

    #[ORM\ManyToOne(targetEntity: Soldier::class)]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[Assert\NotNull]
    #[Groups(['CourseClassStudent', 'CourseClass::read'])]
    private ?Soldier $soldier = null;

    #[ORM\ManyToOne(targetEntity: CourseClass::class, inversedBy: 'students')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups('CourseClassStudent::read')]
    private CourseClass $class;

    #[ORM\Column(enumType: CourseResult::class, nullable: true)]
    #[Groups(['CourseClassStudent', 'CourseClass::read'])]
    private ?CourseResult $result = null;

    /** @var array<int, int|null>|null */
    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['CourseClassStudent', 'CourseClass::read'])]
    private ?array $qualifications = [];

    /** @var array<int, int|null>|null */
    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['CourseClassStudent', 'CourseClass::read'])]
    private ?array $awards = [];

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['CourseClassStudent', 'CourseClass::read'])]
    private ?string $serviceRecordTextOverride = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['CourseClassStudent', 'CourseClass::read'])]
    #[ApiProperty(security: 'object === null or is_granted("MILHQ_COURSE_CLASS_MANAGE", object)')]
    private ?string $notes = null;

    public function getSoldier(): ?Soldier
    {
        return $this->soldier;
    }

    public function setSoldier(?Soldier $user): void
    {
        $this->soldier = $user;
    }

    public function getClass(): CourseClass
    {
        return $this->class;
    }

    public function setClass(CourseClass $class): void
    {
        $this->class = $class;
    }

    public function getResult(): ?CourseResult
    {
        return $this->result;
    }

    public function setResult(?CourseResult $result): void
    {
        $this->result = $result;
    }

    /**
     * @return array<int, int|null>
     */
    public function getQualifications(): array
    {
        return $this->normalizeTierMap($this->qualifications);
    }

    /**
     * @param array<int|string, int|string|null>|null $qualifications
     */
    public function setQualifications(?array $qualifications): void
    {
        $normalized = $this->normalizeTierMap($qualifications);
        $this->qualifications = $normalized === [] ? null : $normalized;
    }

    /**
     * @return array<int, int|null>
     */
    public function getAwards(): array
    {
        return $this->normalizeTierMap($this->awards);
    }

    /**
     * @param array<int|string, int|string|null>|null $awards
     */
    public function setAwards(?array $awards): void
    {
        $normalized = $this->normalizeTierMap($awards);
        $this->awards = $normalized === [] ? null : $normalized;
    }

    public function getServiceRecordTextOverride(): ?string
    {
        return $this->serviceRecordTextOverride;
    }

    public function setServiceRecordTextOverride(?string $serviceRecordTextOverride): void
    {
        $this->serviceRecordTextOverride = $serviceRecordTextOverride;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): void
    {
        $this->notes = $notes;
    }

    /**
     * @param array<int|string, int|string|null>|null $map
     * @return array<int, int|null>
     */
    private function normalizeTierMap(?array $map): array
    {
        $normalized = [];
        foreach ($map ?? [] as $id => $tierId) {
            $normalized[(int)$id] = $tierId === null || $tierId === '' ? null : (int)$tierId;
        }

        return $normalized;
    }
}
