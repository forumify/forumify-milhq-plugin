<?php

declare(strict_types=1);

namespace Forumify\Milhq\Entity;

use Doctrine\ORM\Mapping as ORM;
use Forumify\Core\Entity\IdentifiableEntityTrait;

#[ORM\Entity]
#[ORM\Table('milhq_course_class_instructor')]
class CourseClassInstructor
{
    use IdentifiableEntityTrait;

    #[ORM\ManyToOne(targetEntity: Soldier::class)]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    private ?Soldier $soldier = null;

    #[ORM\ManyToOne(targetEntity: CourseClass::class, inversedBy: 'instructors')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private CourseClass $class;

    #[ORM\ManyToOne(targetEntity: CourseInstructor::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?CourseInstructor $instructor = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $present = null;

    public function getSoldier(): ?Soldier
    {
        return $this->soldier;
    }

    public function setSoldier(Soldier $soldier): void
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
