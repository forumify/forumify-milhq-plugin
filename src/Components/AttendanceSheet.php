<?php

declare(strict_types=1);

namespace Forumify\Milhq\Components;

use DateTime;
use Doctrine\Common\Collections\Collection;
use Forumify\Milhq\Entity\AfterActionReport;
use Forumify\Milhq\Entity\Operation;
use Forumify\Milhq\Entity\Position;
use Forumify\Milhq\Entity\Mission;
use Forumify\Milhq\Entity\Soldier;
use Forumify\Milhq\Entity\Specialty;
use Forumify\Milhq\Entity\Unit;
use Forumify\Milhq\Repository\AfterActionReportAttendanceRepository;
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
    /** @var array<int, Unit>|null */
    public ?array $units = null;
    /** @var array<int, Mission>|null */
    public ?array $missions = null;
    /** @var array<int, array<Soldier>>|null */
    public ?array $users = null;
    /** @var array<int, array<int, string>>|null */
    public ?array $sheet = null;

    public function __construct(
        private readonly AfterActionReportRepository $aarRepository,
        private readonly SoldierService $userService,
        private readonly AfterActionReportAttendanceRepository $attendanceRepository,
    ) {
    }

    /**
     * @return FormInterface<array<string, mixed>|null>
     */
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

        /** @var array{from: DateTime, to: DateTime, unit: Collection<int, Unit>, position: Collection<int, Position>, specialty: Collection<int, Specialty>, operation: Collection<int, Operation>} $data */
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
            $data['unit']->toArray(),
            $data['operation']->toArray(),
        );
        $missions = [];
        $units = [];

        foreach ($aars as $aar) {
            $mission = $aar->getMission();
            $missions[$mission->getId()] = $mission;

            $unit = $aar->getUnit();
            if ($unit === null) {
                continue;
            }
            $units[$unit->getId()] = $unit;
        }
        uasort($units, fn(Unit $a, Unit $b): int => $a->getPosition() <=> $b->getPosition());

        $users = [];
        foreach ($units as $i => $unit) {
            $unitUsers = $unit->getSoldiers()->filter(function (Soldier $soldier) use ($specialties, $positions) {
                if (!empty($specialties) && !in_array($soldier->getSpecialty()?->getId(), $specialties, true)) {
                    return false;
                }
                if (!empty($positions) && !in_array($soldier->getPosition()?->getId(), $positions, true)) {
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

        $this->sheet = $this->buildSheetData($aars, $missions, $users);
        $this->missions = $missions;
        $this->units = $units;
        $this->users = $users;
    }

    /**
     * @param array<AfterActionReport> $aars
     * @param array<int, Mission> $missions
     * @param array<int, array<Soldier>> $users
     * @return array<int, array<int, string>>
     */
    private function buildSheetData(array $aars, array $missions, array $users): array
    {
        $displayedSoldierIds = [];
        foreach ($users as $unitSoldiers) {
            foreach ($unitSoldiers as $soldier) {
                $displayedSoldierIds[$soldier->getId()] = true;
            }
        }

        $sheetData = array_fill_keys(array_keys($missions), []);
        foreach ($this->attendanceRepository->findStatesByAfterActionReports($aars) as $row) {
            if (isset($displayedSoldierIds[$row['soldierId']])) {
                $sheetData[$row['missionId']][$row['soldierId']] = $row['state'];
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

    /**
     * @param array<string> $states
     */
    private function getUserPercentage(int $userId, array $states): ?int
    {
        return $this->getPercentage(array_column($this->sheet ?? [], $userId), $states);
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

    private function getMissionTotal(int $missionId, string $state): int
    {
        return count(array_keys($this->sheet[$missionId] ?? [], $state, true));
    }

    public function missionPercentageAttended(int $missionId): ?int
    {
        return $this->getMissionPercentage($missionId, ['present']);
    }

    public function missionPercentageAccountable(int $missionId): ?int
    {
        return $this->getMissionPercentage($missionId, ['present', 'excused']);
    }

    /**
     * @param array<string> $states
     */
    private function getMissionPercentage(int $missionId, array $states): ?int
    {
        return $this->getPercentage($this->sheet[$missionId] ?? [], $states);
    }

    /**
     * @param array<string> $recordedStates
     * @param array<string> $states
     */
    private function getPercentage(array $recordedStates, array $states): ?int
    {
        if (empty($recordedStates)) {
            return null;
        }

        $matching = array_filter($recordedStates, fn (string $state) => in_array($state, $states, true));
        return (int)(count($matching) / count($recordedStates) * 100);
    }
}
