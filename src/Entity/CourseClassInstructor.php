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
use Forumify\Core\Entity\IdentifiableEntityTrait;
use Forumify\Plugin\Attribute\PluginVersion;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[PluginVersion('forumify/forumify-milhq-plugin', 'premium')]
#[ORM\Entity]
#[ORM\Table('milhq_course_class_instructor')]
#[UniqueEntity(['class', 'soldier'], errorPath: 'soldier')]
#[ApiResource(
    routePrefix: '/milhq',
    normalizationContext: ['groups' => ['MilhqSummary']],
    operations: [
        new Get(extraProperties: ['acl' => ['permission' => 'view_classes', 'entity' => 'class.course']]),
        new Patch(extraProperties: ['acl' => ['permission' => 'manage_classes', 'entity' => 'class.course']]),
        new Delete(extraProperties: ['acl' => ['permission' => 'manage_classes', 'entity' => 'class.course']]),
        new GetCollection(
            uriTemplate: '/course-classes/{classId}/instructors',
            uriVariables: [
                'classId' => new Link(fromClass: CourseClass::class, toProperty: 'class'),
            ],
            extraProperties: ['acl' => ['permission' => 'view_classes', 'entity' => 'class.course']],
        ),
        new Post(
            uriTemplate: '/course-classes/{classId}/instructors',
            uriVariables: [
                'classId' => new Link(
                    fromClass: CourseClass::class,
                    toProperty: 'class',
                    security: 'is_granted("ACCESS_CONTROL_LIST", {"permission": "manage_classes", "entity": courseClass.getCourse()})',
                    securityObjectName: 'courseClass',
                ),
            ],
            provider: CreateProvider::class,
        ),
    ],
)]
class CourseClassInstructor
{
    use IdentifiableEntityTrait;

    #[ORM\ManyToOne(targetEntity: Soldier::class)]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[Assert\NotNull]
    #[Groups(['CourseClassInstructor', 'CourseClass::read'])]
    private ?Soldier $soldier = null;

    #[ORM\ManyToOne(targetEntity: CourseClass::class, inversedBy: 'instructors')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups('CourseClassInstructor::read')]
    private CourseClass $class;

    #[ORM\ManyToOne(targetEntity: CourseInstructor::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    #[Groups(['CourseClassInstructor', 'CourseClass::read'])]
    private ?CourseInstructor $instructor = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    #[Groups(['CourseClassInstructor', 'CourseClass::read'])]
    private ?bool $present = null;

    public function getSoldier(): ?Soldier
    {
        return $this->soldier;
    }

    public function setSoldier(?Soldier $soldier): void
    {
        $this->soldier = $soldier;
    }

    public function getClass(): CourseClass
    {
        return $this->class;
    }

    public function setClass(CourseClass $class): void
    {
        $this->class = $class;
    }

    public function getInstructor(): ?CourseInstructor
    {
        return $this->instructor;
    }

    public function setInstructor(?CourseInstructor $instructor): void
    {
        $this->instructor = $instructor;
    }

    public function isPresent(): ?bool
    {
        return $this->present;
    }

    public function setPresent(?bool $present): void
    {
        $this->present = $present;
    }
}
