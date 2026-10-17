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
use Forumify\Core\Entity\AuditableEntityInterface;
use Forumify\Core\Entity\IdentifiableEntityTrait;
use Forumify\Core\Entity\SortableEntityInterface;
use Forumify\Core\Entity\SortableEntityTrait;
use Forumify\Milhq\Repository\CourseInstructorRepository;
use Forumify\Plugin\Attribute\PluginVersion;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[PluginVersion('forumify/forumify-milhq-plugin', 'premium')]
#[ORM\Entity(repositoryClass: CourseInstructorRepository::class)]
#[ORM\Table(name: 'milhq_course_instructor')]
#[ApiResource(
    routePrefix: '/milhq',
    operations: [
        new Get(extraProperties: ['acl' => ['permission' => 'view', 'entity' => 'course']]),
        new Patch(security: 'is_granted("milhq.admin.courses.manage")'),
        new Delete(security: 'is_granted("milhq.admin.courses.manage")'),
        new GetCollection(
            uriTemplate: '/courses/{courseId}/instructors',
            uriVariables: [
                'courseId' => new Link(fromClass: Course::class, toProperty: 'course'),
            ],
            extraProperties: ['acl' => ['permission' => 'view', 'entity' => 'course']],
        ),
        new Post(
            uriTemplate: '/courses/{courseId}/instructors',
            uriVariables: [
                'courseId' => new Link(fromClass: Course::class, toProperty: 'course'),
            ],
            security: 'is_granted("milhq.admin.courses.manage")',
            provider: CreateProvider::class,
        ),
    ],
)]
class CourseInstructor implements SortableEntityInterface, AuditableEntityInterface
{
    use IdentifiableEntityTrait;
    use SortableEntityTrait;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(allowNull: false)]
    #[Groups(['CourseInstructor', 'Course::read', 'MilhqSummary'])]
    private string $title;

    #[ORM\Column(type: 'text')]
    #[Groups(['CourseInstructor', 'Course::read'])]
    private string $description = '';

    #[ORM\ManyToOne(targetEntity: Course::class, inversedBy: 'instructors')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups('CourseInstructor::read')]
    private Course $course;

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

    public function getCourse(): Course
    {
        return $this->course;
    }

    public function setCourse(Course $course): void
    {
        $this->course = $course;
    }

    public function getIdentifierForAudit(): string
    {
        return (string)$this->getId();
    }

    public function getNameForAudit(): string
    {
        return $this->getCourse()->getTitle();
    }
}
