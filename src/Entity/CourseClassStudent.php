<?php

declare(strict_types=1);

namespace Forumify\Milhq\Entity;

use Doctrine\ORM\Mapping as ORM;
use Forumify\Core\Entity\IdentifiableEntityTrait;

#[ORM\Entity]
#[ORM\Table('milhq_course_class_student')]
class CourseClassStudent
{
    use IdentifiableEntityTrait;

    #[ORM\ManyToOne(targetEntity: Soldier::class)]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    private ?Soldier $soldier = null;

    #[ORM\ManyToOne(targetEntity: CourseClass::class, inversedBy: 'students')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private CourseClass $class;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $result = null;

    #[ORM\Column(type: 'simple_array', nullable: true)]
    private ?array $qualifications = [];

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $serviceRecordTextOverride = null;

    public function getSoldier(): ?Soldier
    {
        return $this->soldier;
    }

    public function setSoldier(Soldier $user): void
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

    public function getResult(): ?string
    {
        return $this->result;
    }

    public function setResult(?string $result): void
    {
        $this->result = $result;
    }

    /**
     * @return array<int>
     */
    public function getQualifications(): array
    {
        return array_map(fn (mixed $id) => (int)$id, $this->qualifications ?? []);
    }

    public function setQualifications(?array $qualifications): void
    {
        $this->qualifications = $qualifications;
    }

    public function getServiceRecordTextOverride(): ?string
    {
        return $this->serviceRecordTextOverride;
    }

    public function setServiceRecordTextOverride(?string $serviceRecordTextOverride): void
    {
        $this->serviceRecordTextOverride = $serviceRecordTextOverride;
    }
}
