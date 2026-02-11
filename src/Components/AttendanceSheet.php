<?php

declare(strict_types=1);

namespace Forumify\Milhq\Components;

use DateTime;
use Forumify\Milhq\Entity\AfterActionReport;
use Forumify\Milhq\Entity\Operation;
use Forumify\Milhq\Entity\Position;
use Forumify\Milhq\Entity\Mission;
use Forumify\Milhq\Entity\Soldier;
use Forumify\Milhq\Entity\Specialty;
use Forumify\Milhq\Entity\Unit;
use Forumify\Milhq\Repository\AfterActionReportRepository;
use Forumify\Milhq\Service\SoldierService;
use Forumify\Plugin\Attribute\PluginVersion;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[PluginVersion('forumify/forumify-milhq-plugin', 'premium')]
#[AsLiveComponent('Milhq\\AttendanceSheet', '@ForumifyMilhqPlugin/frontend/components/attendance_sheet.html.twig')]
#[IsGranted('milhq.frontend.attendance_sheet.view')]
class AttendanceSheet extends AbstractController
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;

    public ?string $error = null;
    public ?array $units = null;
    public ?array $missions = null;
    public ?array $users = null;
    public ?array $sheet = null;

    public function __construct(
        private readonly AfterActionReportRepository $aarRepository,
        private readonly SoldierService $userService,
    ) {
    }

    protected function instantiateForm(): FormInterface
    {
        return $this->createFormBuilder()
            ->add('from', DateType::class, [
                'data' => (new DateTime())->sub(new \DateInterval('P1M')),
                'widget' => 'single_text',
            ])
            ->add('to', DateType::class, [
                'data' => new DateTime(),
                'widget' => 'single_text',
            ])
            ->add('unit', EntityType::class, [
                'autocomplete' => true,
                'choice_label' => 'name',
                'placeholder' => 'All units',
                'class' => Unit::class,
                'multiple' => true,
                'required' => false,
            ])
            ->add('position', EntityType::class, [
                'autocomplete' => true,
                'choice_label' => 'name',
                'placeholder' => 'All positions',
                'class' => Position::class,
                'multiple' => true,
                'required' => false,
            ])
            ->add('specialty', EntityType::class, [
                'autocomplete' => true,
                'choice_label' => 'name',
                'placeholder' => 'All specialties',
                'class' => Specialty::class,
                'multiple' => true,
                'required' => false,
            ])
            ->add('operation', EntityType::class, [
                'autocomplete' => true,
                'choice_label' => 'title',
                'placeholder' => 'All operations',
                'class' => Operation::class,
                'multiple' => true,
                'required' => false,
            ])
            ->getForm()
        ;
    }

    // @phpstan-ignore-next-line
    private function getDataModelValue(): ?string
    {
        return 'norender|*';
    }

    #[LiveAction]
    public function calculate(): void
    {
        $this->submitForm();

        $data = $this->getForm()->getData();

        $data['from']->setTime(0, 0, 0);
        $data['to']->setTime(23, 59, 59);

        $diff = (int)$data['from']->diff($data['to'])->format('%r%a');
        if ($diff <= 0) {
            $this->error = 'You have selected an invalid from/to range. It can not be negative.';
            return;
        }

        if ($diff > 6 * 31) {
            $this->error = 'You have selected an invalid from/to range. It can not be larger than 6 months.';
            return;
        }

        $specialties = $data['specialty']->map(fn (Specialty $s) => $s->getId())->toArray();
        $positions = $data['position']->map(fn (Position $p) => $p->getId())->toArray();

        $aars = $this->aarRepository->findByMissionStartAndUnit(
            $data['from'],
            $data['to'],
            $data['unit']?->toArray() ?? [],
            $data['operation']?->toArray() ?? [],
        );
        $missions = [];
        $units = [];

        foreach ($aars as $aar) {
            $mission = $aar->getMission();
            $missions[$mission->getId()] = $mission;

            $unit = $aar->getUnit();
            $units[$unit->getId()] = $unit;
        }
        uasort($units, fn(Unit $a, Unit $b): int => $a->getPosition() <=> $b->getPosition());

        $users = [];
        foreach ($units as $i => $unit) {
            $unitUsers = $unit->getSoldiers()->filter(function (Soldier $soldier) use ($specialties, $positions) {
                if (!empty($specialties) && !in_array($soldier->getSpecialty()->getId(), $specialties, true)) {
                    return false;
                }
                if (!empty($positions) && !in_array($soldier->getPosition()->getId(), $positions, true)) {
                    return false;
                }
                return true;
            })->toArray();

            if (empty($unitUsers)) {
                unset($units[$i]);
                continue;
            }

            $this->userService->sortSoldiers($unitUsers);
            $users[$unit->getId()] = $unitUsers;
        }

        $this->sheet = $this->buildSheetData($aars, $missions, $units, $users);
        $this->missions = $missions;
        $this->units = $units;
        $this->users = $users;
    }

    /**
     * @param array<AfterActionReport> $aars
     * @param array<Mission> $missions
     * @param array<Unit> $units
     * @param array<array<Soldier>> $users
     * @return array<int, array<int, array<int, string>>>
     */
    private function buildSheetData(array $aars, array $missions, array $units, array $users): array
    {
        $sheetData = [];
        foreach (array_keys($missions) as $missionId) {
            foreach (array_keys($units) as $unitId) {
                foreach (($users[$unitId] ?? []) as $user) {
                    $sheetData[$missionId][$unitId][$user->getId()] = '';
                }
            }
        }

        foreach ($aars as $aar) {
            $missionId = $aar->getMission()->getId();
            $unitId = $aar->getUnit()->getId();

            foreach ($aar->getAttendance() as $state => $userIds) {
                foreach ($userIds as $userId) {
                    if (isset($sheetData[$missionId][$unitId][$userId])) {
                        $sheetData[$missionId][$unitId][$userId] = $state;
                    } else {
                        // The user changed combat units, let's see if we can find them in a different unit
                        foreach ($sheetData[$missionId] as $mUnitId => $mUserIds) {
                            if (in_array($userId, array_keys($mUserIds))) {
                                $sheetData[$missionId][$mUnitId][$userId] = $state;
                                break;
                            }
                        }
                    }
                }
            }
        }

        return $sheetData;
    }

    #[LiveAction]
    public function reset(): void
    {
        $this->error = null;
        $this->missions = null;
        $this->units = null;
        $this->users = null;
        $this->sheet = null;
        $this->resetForm();
    }

    public function userAttendance(int $userId): ?int
    {
        return $this->getUserPercentage($userId, ['present']) ?? 0;
    }

    public function userAccountability(int $userId): ?int
    {
        return $this->getUserPercentage($userId, ['present', 'excused']) ?? 0;
    }

    private function getUserPercentage(int $userId, array $states): ?int
    {
        $total = 0;
        $count = null;

        foreach ($this->sheet as $units) {
            foreach ($units as $users) {
                foreach ($users as $uid => $state) {
                    if (empty($state)) {
                        continue;
                    }

                    if ($userId !== $uid) {
                        continue;
                    }

                    $total++;
                    if ($count === null) {
                        $count = 0;
                    }

                    if (in_array($state, $states, true)) {
                        $count++;
                    }
                }
            }
        }

        if ($count === null || $total <= 0) {
            return null;
        }

        return (int)($count / $total * 100);
    }

    public function missionTotalPresent(int $missionId): ?int
    {
        return $this->getMissionTotal($missionId, 'present');
    }

    public function missionTotalExcused(int $missionId): ?int
    {
        return $this->getMissionTotal($missionId, 'excused');
    }

    public function missionTotalAbsent(int $missionId): ?int
    {
        return $this->getMissionTotal($missionId, 'absent');
    }

    private function getMissionTotal(int $missionId, string $tState): int
    {
        $count = 0;
        foreach ($this->sheet[$missionId] as $users) {
            foreach ($users as $state) {
                if ($state === $tState) {
                    $count++;
                }
            }
        }

        return $count;
    }

    public function missionPercentageAttended(int $missionId): ?int
    {
        return $this->getMissionPercentage($missionId, ['present']);
    }

    public function missionPercentageAccountable(int $missionId): ?int
    {
        return $this->getMissionPercentage($missionId, ['present', 'excused']);
    }

    private function getMissionPercentage(int $missionId, array $states): ?int
    {
        $total = 0;
        $count = null;

        foreach ($this->sheet[$missionId] as $users) {
            foreach ($users as $state) {
                if (empty($state)) {
                    continue;
                }

                $total++;
                if ($count === null) {
                    $count = 0;
                }

                if (in_array($state, $states, true)) {
                    $count++;
                }
            }
        }

        if ($count === null || $total <= 0) {
            return null;
        }

        return (int)($count / $total * 100);
    }
}
