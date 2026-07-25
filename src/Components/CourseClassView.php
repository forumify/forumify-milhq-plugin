<?php

declare(strict_types=1);

namespace Forumify\Milhq\Components;

use DateTime;
use Forumify\Core\Security\VoterAttribute;
use Forumify\Milhq\Entity\CourseClass;
use Forumify\Milhq\Entity\CourseClassInstructor;
use Forumify\Milhq\Entity\CourseClassStudent;
use Forumify\Milhq\Repository\AwardRepository;
use Forumify\Milhq\Repository\CourseClassInstructorRepository;
use Forumify\Milhq\Repository\CourseClassStudentRepository;
use Forumify\Milhq\Repository\CourseInstructorRepository;
use Forumify\Milhq\Repository\QualificationRecordRepository;
use Forumify\Milhq\Repository\QualificationRepository;
use Forumify\Milhq\Service\SoldierService;
use Forumify\Plugin\Attribute\PluginVersion;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[PluginVersion('forumify/forumify-milhq-plugin', 'premium')]
#[AsLiveComponent('Milhq\\CourseClassView', '@ForumifyMilhqPlugin/frontend/components/course_class/class.html.twig')]
class CourseClassView extends AbstractController
{
    use DefaultActionTrait;

    #[LiveProp]
    public CourseClass $class;

    public function __construct(
        private readonly SoldierService $soldierService,
        private readonly CourseInstructorRepository $instructorRepository,
        private readonly CourseClassStudentRepository $classStudentRepository,
        private readonly CourseClassInstructorRepository $classInstructorRepository,
        private readonly QualificationRecordRepository $qualificationRecordRepository,
        private readonly QualificationRepository $qualificationRepository,
        private readonly AwardRepository $awardRepository,
    ) {
    }

    /**
     * @return array<int, array{
     *     soldier: \Forumify\Milhq\Entity\Soldier,
     *     student: CourseClassStudent,
     *     qualifications: array<array{name: string, tier: string|null}>,
     *     awards: array<array{name: string, tier: string|null}>,
     * }>
     */
    public function getStudentResults(): array
    {
        if ($this->class->getResult() === false) {
            return [];
        }

        $qualIds = [];
        $awardIds = [];
        foreach ($this->class->getStudents() as $student) {
            $qualIds += $student->getQualifications();
            $awardIds += $student->getAwards();
        }

        $qualifications = empty($qualIds)
            ? []
            : $this->indexById($this->qualificationRepository->findBy(['id' => array_keys($qualIds)]));
        $awards = empty($awardIds)
            ? []
            : $this->indexById($this->awardRepository->findBy(['id' => array_keys($awardIds)]));

        $results = [];
        foreach ($this->class->getStudents() as $student) {
            $soldier = $student->getSoldier();
            if ($soldier === null) {
                continue;
            }

            $results[$soldier->getId()] = [
                'soldier' => $soldier,
                'student' => $student,
                'qualifications' => $this->resolveAchievements($student->getQualifications(), $qualifications),
                'awards' => $this->resolveAchievements($student->getAwards(), $awards),
            ];
        }

        return $results;
    }

    /**
     * @param array<int, int|null> $map
     * @param array<int, object> $entities
     * @return array<array{name: string, tier: string|null}>
     */
    private function resolveAchievements(array $map, array $entities): array
    {
        $resolved = [];
        foreach ($map as $id => $tierId) {
            $entity = $entities[$id] ?? null;
            if ($entity === null) {
                continue;
            }

            $tierName = null;
            if ($tierId !== null) {
                foreach ($entity->tiers as $tier) {
                    if ($tier->getId() === $tierId) {
                        $tierName = $tier->name;
                        break;
                    }
                }
            }

            $resolved[] = ['name' => $entity->getName(), 'tier' => $tierName];
        }

        return $resolved;
    }

    /**
     * @param array<object> $entities
     * @return array<int, object>
     */
    private function indexById(array $entities): array
    {
        $indexed = [];
        foreach ($entities as $entity) {
            $indexed[$entity->getId()] = $entity;
        }

        return $indexed;
    }

    public function isSignupOpen(): bool
    {
        $now = new DateTime();
        return $this->class->getResult() === false
            && $now > $this->class->getSignupFrom()
            && $now < $this->class->getSignupUntil();
    }

    public function canSignUpAsStudent(): bool
    {
        $soldier = $this->soldierService->getLoggedInSoldier();
        if ($soldier === null) {
            return false;
        }

        if ($this->getStudentSlots() === 0) {
            return false;
        }

        $qualifications = $this->qualificationRecordRepository
            ->createQueryBuilder('qr')
            ->select('DISTINCT IDENTITY(qr.qualification)')
            ->where('qr.soldier = :soldier')
            ->setParameter('soldier', $soldier)
            ->getQuery()
            ->getSingleColumnResult()
        ;

        $prerequisites = $this->class->getCourse()->getPrerequisites();
        foreach ($prerequisites as $prerequisiteId) {
            if (!in_array($prerequisiteId, $qualifications, true)) {
                return false;
            }
        }

        $minimumRank = $this->class->getCourse()->getMinimumRank();
        if ($minimumRank === null) {
            return true;
        }

        return $minimumRank->getPosition() >= $soldier->getRank()->getPosition();
    }

    public function isSignedUpAsStudent(): bool
    {
        $soldier = $this->soldierService->getLoggedInSoldier();
        if ($soldier === null) {
            return false;
        }

        return $this->classStudentRepository->count([
            'class' => $this->class,
            'soldier' => $soldier,
        ]) > 0;
    }

    #[LiveAction]
    public function toggleStudent(): void
    {
        if (!$this->canSignUpAsStudent()) {
            return;
        }

        $soldier = $this->soldierService->getLoggedInSoldier();
        if ($soldier === null) {
            return;
        }

        $student = $this->classStudentRepository->findOneBy(['soldier' => $soldier, 'class' => $this->class]);
        if ($student === null) {
            $student = new CourseClassStudent();
            $student->setClass($this->class);
            $student->setSoldier($soldier);
            $this->classStudentRepository->save($student);
        } else {
            $this->classStudentRepository->remove($student);
        }
    }

    #[LiveAction]
    public function registerInstructor(#[LiveArg] ?int $instructorId = null): void
    {
        $this->denyAccessUnlessGranted(VoterAttribute::ACL->value, [
            'entity' => $this->class->getCourse(),
            'permission' => 'signup_as_instructor',
        ]);

        $soldier = $this->soldierService->getLoggedInSoldier();
        if ($soldier === null) {
            return;
        }

        $instructor = $this->classInstructorRepository->findOneBy([
            'class' => $this->class,
            'soldier' => $soldier,
        ]);

        if ($instructor !== null) {
            $this->classInstructorRepository->remove($instructor);
            return;
        }

        $instructorType = $instructorId === null ? null : $this->instructorRepository->find($instructorId);

        $cInstructor = new CourseClassInstructor();
        $cInstructor->setSoldier($soldier);
        $cInstructor->setClass($this->class);
        $cInstructor->setInstructor($instructorType);
        $this->classInstructorRepository->save($cInstructor);
    }

    #[LiveAction]
    public function removeStudent(#[LiveArg] int $soldierId): void
    {
        $this->denyAccessUnlessGranted(VoterAttribute::ACL->value, [
            'entity' => $this->class->getCourse(),
            'permission' => 'manage_classes',
        ]);

        $student = $this->classStudentRepository->findOneBy([
            'class' => $this->class,
            'soldier' => $soldierId,
        ]);

        if ($student !== null) {
            $this->classStudentRepository->remove($student);
        }
    }

    #[LiveAction]
    public function removeInstructor(#[LiveArg] int $soldierId): void
    {
        $this->denyAccessUnlessGranted(VoterAttribute::ACL->value, [
            'entity' => $this->class->getCourse(),
            'permission' => 'manage_classes',
        ]);

        $instructor = $this->classInstructorRepository->findOneBy([
            'class' => $this->class,
            'soldier' => $soldierId,
        ]);

        if ($instructor !== null) {
            $this->classInstructorRepository->remove($instructor);
        }
    }

    public function isSignedUpAsInstructor(): bool
    {
        $soldier = $this->soldierService->getLoggedInSoldier();
        if ($soldier === null) {
            return false;
        }

        return $this->classInstructorRepository->count([
            'class' => $this->class,
            'soldier' => $soldier,
        ]) > 0;
    }

    public function getStudentSlots(): int
    {
        if (!$this->isSignupOpen()) {
            return 0;
        }

        $classSlots = $this->class->getStudentSlots();
        if ($classSlots === null) {
            return 3;
        }

        if ($classSlots === 0) {
            return 0;
        }
        return max(0, $classSlots - $this->class->getStudents()->count());
    }
}
