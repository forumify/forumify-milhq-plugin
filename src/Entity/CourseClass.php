<?php

declare(strict_types=1);

namespace Forumify\Milhq\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Forumify\Calendar\Entity\Calendar;
use Forumify\Calendar\Entity\CalendarEvent;
use Forumify\Core\Entity\BlameableEntityTrait;
use Forumify\Core\Entity\IdentifiableEntityTrait;
use Forumify\Core\Entity\TimestampableEntityTrait;
use Forumify\Milhq\Repository\CourseClassRepository;

if (class_exists(\Forumify\Calendar\ForumifyCalendarPlugin::class)) {
    #[ORM\Entity(repositoryClass: CourseClassRepository::class)]
    #[ORM\Table('milhq_course_class')]
    class CourseClass
    {
        use IdentifiableEntityTrait;
        use BlameableEntityTrait;
        use TimestampableEntityTrait;

        #[ORM\ManyToOne(targetEntity: Course::class, inversedBy: 'classes')]
        #[ORM\JoinColumn(onDelete: 'CASCADE')]
        private Course $course;

        #[ORM\Column(length: 255)]
        private string $title;

        #[ORM\Column(type: 'text')]
        private string $description;

        #[ORM\Column(type: 'datetime')]
        private DateTime $signupFrom;

        #[ORM\Column(type: 'datetime')]
        private DateTime $signupUntil;

        #[ORM\Column(type: 'datetime')]
        private DateTime $start;

        #[ORM\Column(type: 'datetime')]
        private DateTime $end;

        /**
         * @var Collection<int, CourseClassInstructor>
         */
        #[ORM\OneToMany(
            mappedBy: 'class',
            targetEntity: CourseClassInstructor::class,
            cascade: ['persist', 'remove'],
            orphanRemoval: true,
        )]
        private Collection $instructors;

        /**
         * @var Collection<int, CourseClassStudent>
         */
        #[ORM\OneToMany(
            mappedBy: 'class',
            targetEntity: CourseClassStudent::class,
            cascade: ['persist', 'remove'],
            orphanRemoval: true,
        )]
        private Collection $students;

        #[ORM\Column(type: 'integer', nullable: true)]
        private ?int $studentSlots = null;

        #[Column(type: 'boolean', options: ['default' => false])]
        private bool $result = false;

        #[ORM\ManyToOne(targetEntity: Calendar::class)]
        #[ORM\JoinColumn(onDelete: 'SET NULL')]
        private ?Calendar $calendar = null;

        #[ORM\OneToOne(targetEntity: CalendarEvent::class)]
        #[ORM\JoinColumn(onDelete: 'SET NULL')]
        private ?CalendarEvent $calendarEvent = null;

        public function __construct()
        {
            $this->instructors = new ArrayCollection();
            $this->students = new ArrayCollection();
        }

        public function getCourse(): Course
        {
            return $this->course;
        }

        public function setCourse(Course $course): void
        {
            $this->course = $course;
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

        public function getSignupFrom(): DateTime
        {
            return $this->signupFrom;
        }

        public function setSignupFrom(DateTime $signupFrom): void
        {
            $this->signupFrom = $signupFrom;
        }

        public function getSignupUntil(): DateTime
        {
            return $this->signupUntil;
        }

        public function setSignupUntil(DateTime $signupUntil): void
        {
            $this->signupUntil = $signupUntil;
        }

        public function getStart(): DateTime
        {
            return $this->start;
        }

        public function setStart(DateTime $start): void
        {
            $this->start = $start;
        }

        public function getEnd(): DateTime
        {
            return $this->end;
        }

        public function setEnd(DateTime $end): void
        {
            $this->end = $end;
        }

        /**
         * @return Collection<int, CourseClassInstructor>
         */
        public function getInstructors(): Collection
        {
            return $this->instructors;
        }

        public function setInstructors(Collection|array $instructors): void
        {
            $this->instructors = $instructors instanceof Collection
                ? $instructors
                : new ArrayCollection($instructors);
        }

        public function addInstructor(CourseClassInstructor $instructor): void
        {
            $this->instructors->add($instructor);
            $instructor->setClass($this);
        }

        public function removeInstructor(CourseClassInstructor $instructor): void
        {
            $this->instructors->removeElement($instructor);
        }

        /**
         * @return Collection<int, CourseClassStudent>
         */
        public function getStudents(): Collection
        {
            return $this->students;
        }

        public function setStudents(Collection|array $students): void
        {
            $this->students = $students instanceof Collection
                ? $students
                : new ArrayCollection($students);
        }

        public function addStudent(CourseClassStudent $student): void
        {
            $this->students->add($student);
            $student->setClass($this);
        }

        public function removeStudent(CourseClassStudent $student): void
        {
            $this->students->removeElement($student);
        }

        public function getStudentSlots(): ?int
        {
            return $this->studentSlots;
        }

        public function setStudentSlots(?int $studentSlots): void
        {
            $this->studentSlots = $studentSlots;
        }

        public function getResult(): bool
        {
            return $this->result;
        }

        public function setResult(bool $result): void
        {
            $this->result = $result;
        }

        public function getCalendar(): ?Calendar
        {
            return $this->calendar;
        }

        public function setCalendar(?Calendar $calendar): void
        {
            $this->calendar = $calendar;
        }

        public function getEvent(): ?CalendarEvent
        {
            return $this->calendarEvent;
        }

        public function setEvent(?CalendarEvent $event): void
        {
            $this->calendarEvent = $event;
        }
    }
} else {
    #[ORM\Entity(repositoryClass: CourseClassRepository::class)]
    #[ORM\Table('milhq_course_class')]
    // phpcs:ignore
    class CourseClass
    {
        use IdentifiableEntityTrait;
        use BlameableEntityTrait;
        use TimestampableEntityTrait;

        #[ORM\ManyToOne(targetEntity: Course::class, inversedBy: 'classes')]
        #[ORM\JoinColumn(onDelete: 'CASCADE')]
        private Course $course;

        #[ORM\Column(length: 255)]
        private string $title;

        #[ORM\Column(type: 'text')]
        private string $description;

        #[ORM\Column(type: 'datetime')]
        private DateTime $signupFrom;

        #[ORM\Column(type: 'datetime')]
        private DateTime $signupUntil;

        #[ORM\Column(type: 'datetime')]
        private DateTime $start;

        #[ORM\Column(type: 'datetime')]
        private DateTime $end;

        /**
         * @var Collection<int, CourseClassInstructor>
         */
        #[ORM\OneToMany(
            mappedBy: 'class',
            targetEntity: CourseClassInstructor::class,
            cascade: ['persist', 'remove'],
            orphanRemoval: true,
        )]
        private Collection $instructors;

        /**
         * @var Collection<int, CourseClassStudent>
         */
        #[ORM\OneToMany(
            mappedBy: 'class',
            targetEntity: CourseClassStudent::class,
            cascade: ['persist', 'remove'],
            orphanRemoval: true,
        )]
        private Collection $students;

        #[ORM\Column(type: 'integer', nullable: true)]
        private ?int $studentSlots = null;

        #[Column(type: 'boolean', options: ['default' => false])]
        private bool $result = false;

        public function __construct()
        {
            $this->instructors = new ArrayCollection();
            $this->students = new ArrayCollection();
        }

        public function getCourse(): Course
        {
            return $this->course;
        }

        public function setCourse(Course $course): void
        {
            $this->course = $course;
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

        public function getSignupFrom(): DateTime
        {
            return $this->signupFrom;
        }

        public function setSignupFrom(DateTime $signupFrom): void
        {
            $this->signupFrom = $signupFrom;
        }

        public function getSignupUntil(): DateTime
        {
            return $this->signupUntil;
        }

        public function setSignupUntil(DateTime $signupUntil): void
        {
            $this->signupUntil = $signupUntil;
        }

        public function getStart(): DateTime
        {
            return $this->start;
        }

        public function setStart(DateTime $start): void
        {
            $this->start = $start;
        }

        public function getEnd(): DateTime
        {
            return $this->end;
        }

        public function setEnd(DateTime $end): void
        {
            $this->end = $end;
        }

        /**
         * @return Collection<int, CourseClassInstructor>
         */
        public function getInstructors(): Collection
        {
            return $this->instructors;
        }

        public function setInstructors(Collection|array $instructors): void
        {
            $this->instructors = $instructors instanceof Collection
                ? $instructors
                : new ArrayCollection($instructors);
        }

        public function addInstructor(CourseClassInstructor $instructor): void
        {
            $this->instructors->add($instructor);
            $instructor->setClass($this);
        }

        public function removeInstructor(CourseClassInstructor $instructor): void
        {
            $this->instructors->removeElement($instructor);
        }

        /**
         * @return Collection<int, CourseClassStudent>
         */
        public function getStudents(): Collection
        {
            return $this->students;
        }

        public function setStudents(Collection|array $students): void
        {
            $this->students = $students instanceof Collection
                ? $students
                : new ArrayCollection($students);
        }

        public function addStudent(CourseClassStudent $student): void
        {
            $this->students->add($student);
            $student->setClass($this);
        }

        public function removeStudent(CourseClassStudent $student): void
        {
            $this->students->removeElement($student);
        }

        public function getStudentSlots(): ?int
        {
            return $this->studentSlots;
        }

        public function setStudentSlots(?int $studentSlots): void
        {
            $this->studentSlots = $studentSlots;
        }

        public function getResult(): bool
        {
            return $this->result;
        }

        public function setResult(bool $result): void
        {
            $this->result = $result;
        }
    }
}
