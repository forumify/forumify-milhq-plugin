<?php

declare(strict_types=1);

namespace Forumify\Milhq\Entity;

use Doctrine\ORM\Mapping as ORM;
use Forumify\Core\Entity\IdentifiableEntityTrait;
use Forumify\Milhq\Entity\Enum\CourseResult;

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

    #[ORM\Column(enumType: CourseResult::class, nullable: true)]
    private ?CourseResult $result = null;

    /** @var array<int, int|null>|null */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $qualifications = [];

    /** @var array<int, int|null>|null */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $awards = [];

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $serviceRecordTextOverride = null;

    #[ORM\Column(type: 'text', nullable: true)]
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
