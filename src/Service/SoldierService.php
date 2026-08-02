<?php

declare(strict_types=1);

namespace Forumify\Milhq\Service;

use DateInterval;
use DateTime;
use DateTimeInterface;
use Forumify\Core\Entity\SortableEntityInterface;
use Forumify\Core\Entity\User;
use Forumify\Core\Repository\SettingRepository;
use Forumify\Milhq\Entity\Equipment;
use Forumify\Milhq\Entity\GroupedEntityInterface;
use Forumify\Milhq\Entity\Record\AssignmentRecord;
use Forumify\Milhq\Form\Enlistment;
use Forumify\Milhq\Entity\Soldier;
use Forumify\Milhq\Entity\Unit;
use Forumify\Milhq\Repository\AssignmentRecordRepository;
use Forumify\Milhq\Repository\RankRecordRepository;
use Forumify\Milhq\Repository\ReportInRepository;
use Forumify\Milhq\Repository\SoldierRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Contracts\Translation\TranslatorInterface;

class SoldierService
{
    private array $userIdToSoldier = [];

    public function __construct(
        private readonly SoldierRepository $soldierRepository,
        private readonly SettingRepository $settingRepository,
        private readonly Security $security,
        private readonly RankRecordRepository $rankRecordRepository,
        private readonly AssignmentRecordRepository $assignmentRecordRepository,
        private readonly ReportInRepository $reportInRepository,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function getLoggedInSoldier(): ?Soldier
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return null;
        }

        return $this->getSoldier($user);
    }

    public function getSoldier(User $user): ?Soldier
    {
        $userId = $user->getId();
        if (isset($this->userIdToSoldier[$userId])) {
            return $this->userIdToSoldier[$userId];
        }

        $this->userIdToSoldier[$userId] = $this->soldierRepository->findOneBy(['user' => $user]);
        return $this->userIdToSoldier[$userId];
    }

    public function createUser(Enlistment $enlistment): Soldier
    {
        /** @var User $user */
        $user = $this->security->getUser();
        $soldier = new Soldier();
        $soldier->setUser($user);
        $soldier->setName($user->getDisplayName());

        if (!empty($enlistment->firstName) && !empty($enlistment->lastName)) {
            $name = ucfirst($enlistment->firstName) . ' ' . ucfirst($enlistment->lastName);
            $soldier->setName($name);
        }

        $this->soldierRepository->save($soldier);
        return $soldier;
    }

    public function sortSoldiers(&$users): void
    {
        $sortOrder = $this->settingRepository->get('milhq.roster.user_sort_order');
        $sortOrder = empty($sortOrder)
            ? ['rank', 'position', 'specialty']
            : array_map('trim', explode(',', $sortOrder));

        $propertyAccessor = PropertyAccess::createPropertyAccessorBuilder()
            ->disableExceptionOnInvalidPropertyPath()
            ->getPropertyAccessor();

        usort($users, static function (Soldier $a, Soldier $b) use ($propertyAccessor, $sortOrder): int {
            foreach ($sortOrder as $sortField) {
                $valA = $propertyAccessor->getValue($a, $sortField);
                $valB = $propertyAccessor->getValue($b, $sortField);

                if ($valA instanceof GroupedEntityInterface && $valB instanceof GroupedEntityInterface) {
                    $groupPosA = $valA->getGroup()?->getPosition() ?? -1;
                    $gorupPosB = $valB->getGroup()?->getPosition() ?? -1;
                    if ($groupPosA !== $gorupPosB) {
                        return $groupPosA - $gorupPosB;
                    }
                }

                $aIsSortable = $valA instanceof SortableEntityInterface;
                $bIsSortable = $valB instanceof SortableEntityInterface;

                if ($aIsSortable && $bIsSortable) {
                    $valA = $valA->getPosition();
                    $valB = $valB->getPosition();
                    if ($valA === $valB) {
                        continue;
                    }
                    return $valA - $valB;
                }

                $diff = (int)$bIsSortable - (int)$aIsSortable;
                if ($diff !== 0) {
                    return $diff;
                }
            }
            return strcmp($a->getName(), $b->getName());
        });
    }

    public function getTimeInService(Soldier $soldier): string
    {
        $diff = $soldier->getCreatedAt()->diff(new DateTime());
        return $this->formatTimeInX($diff);
    }

    public function getTimeInGrade(Soldier $soldier): string
    {
        $rankRecords = $this->rankRecordRepository
            ->createQueryBuilder('rr')
            ->select('MAX(rr.createdAt)')
            ->where('rr.soldier = :soldier')
            ->setParameter('soldier', $soldier)
            ->getQuery()
            ->getResult()
        ;

        $lastRankRecord = reset($rankRecords);
        if (!$lastRankRecord) {
            return '';
        }

        $lastDate = reset($lastRankRecord);
        if (!$lastDate) {
            return '';
        }

        $diff = (new DateTime($lastDate))->diff(new DateTime());
        return $this->formatTimeInX($diff);
    }

    private function formatTimeInX(DateInterval $interval): string
    {
        $parts = array_filter([
            $this->translator->trans('date_relative.years', ['count' => $interval->format('%y')]),
            $this->translator->trans('date_relative.months', ['count' => $interval->format('%m')]),
            $this->translator->trans('date_relative.days', ['count' => $interval->format('%d')]),
        ]);
        return implode(', ', $parts);
    }

    /**
     * @return array<Soldier>
     */
    public function getSupervisors(Soldier $soldier): array
    {
        $unit = $soldier->getUnit();
        if ($unit === null) {
            return [];
        }

        $supervisors = $this->getUnitSupervisors($unit);
        if ($soldier->getPosition() === null) {
            return $supervisors;
        }

        $supervisors = array_filter(
            $supervisors,
            fn (Soldier $supervisor) => $soldier->getPosition()->getPosition() > $supervisor->getPosition()->getPosition(),
        );
        usort($supervisors, fn (Soldier $a, Soldier $b) => $a->getPosition()->getPosition() <=> $b->getPosition()->getPosition());
        return $supervisors;
    }

    /**
     * @return array<Soldier>
     */
    public function getUnitSupervisors(Unit $unit): array
    {
        if ($unit->supervisors->isEmpty()) {
            return [];
        }

        return $this->soldierRepository->findBy([
            'position' => $unit->supervisors->toArray(),
            'unit' => $unit,
        ]);
    }

    /**
     * @return array<Soldier>
     */
    public function getSoldiersInUnit(Unit $unit): array
    {
        $allSoldiers = [];

        $primaryAssigned = $unit->getSoldiers()->toArray();
        foreach ($primaryAssigned as $soldier) {
            $allSoldiers[$soldier->getId()] = $soldier;
        }

        $secondaryAssigned = $this->assignmentRecordRepository
            ->createQueryBuilder('ar')
            ->select('ar')
            ->join('ar.soldier', 's')
            ->where('ar.type = :type')
            ->andWhere('ar.unit = :unit')
            ->setParameter('type', 'secondary')
            ->setParameter('unit', $unit)
            ->getQuery()
            ->getResult()
        ;

        /** @var AssignmentRecord $secondary */
        foreach ($secondaryAssigned as $secondary) {
            $soldier = $secondary->getSoldier();
            $allSoldiers[$soldier->getId()] = $soldier;
            $soldier->setPosition($secondary->getPosition());
            $soldier->setSpecialty($secondary->getSpecialty());
        }

        $this->sortSoldiers($allSoldiers);
        return $allSoldiers;
    }

    /**
     * @return array{
     *     primaryWeapons: array<Equipment>,
     *     secondaryWeapons: array<Equipment>,
     *     vehicles: array<Equipment>
     * }
     */
    public function getEquipment(Soldier $soldier): array
    {
        $primaryWeapons = [];
        $secondaryWeapons = [];
        $vehicles = [];

        /** @var array<AssignmentRecord> $records */
        $records = $this->assignmentRecordRepository->findBy([
            'type' => 'secondary',
            'soldier' => $soldier,
        ]);

        $positions = array_map(fn(AssignmentRecord $record) => $record->getPosition(), $records);
        $positions[] = $soldier->getPosition();
        foreach (array_filter($positions) as $position) {
            foreach ($position->getPrimaryWeapons() as $weapon) {
                $primaryWeapons[$weapon->getId()] = $weapon;
            }
            foreach ($position->getSecondaryWeapons() as $weapon) {
                $secondaryWeapons[$weapon->getId()] = $weapon;
            }
        }

        $units = array_map(fn(AssignmentRecord $record) => $record->getUnit(), $records);
        $units[] = $soldier->getUnit();
        foreach (array_filter($units) as $unit) {
            foreach ($unit->getVehicles() as $vehicle) {
                $vehicles[$vehicle->getId()] = $vehicle;
            }
        }

        return [
            'primaryWeapons' => $primaryWeapons,
            'secondaryWeapons' => $secondaryWeapons,
            'vehicles' => $vehicles,
        ];
    }

    public function getLastReportInDate(Soldier $soldier): ?DateTimeInterface
    {
        return $this->reportInRepository->findOneBy(['soldier' => $soldier])?->getLastReportInDate();
    }
}
