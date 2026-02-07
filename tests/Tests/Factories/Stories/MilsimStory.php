<?php

declare(strict_types=1);

namespace PluginTests\Tests\Factories\Stories;

use Doctrine\Common\Collections\ArrayCollection;
use Forumify\Core\Entity\MenuItem;
use Forumify\Core\Repository\MenuItemRepository;
use Forumify\Core\Repository\SettingRepository;
use Forumify\Milhq\Entity;
use PluginTests\Tests\Factories\Forumify\ForumFactory;
use PluginTests\Tests\Factories\Milhq\FormFactory;
use PluginTests\Tests\Factories\Milhq\FormFieldFactory;
use PluginTests\Tests\Factories\Milhq\PositionFactory;
use PluginTests\Tests\Factories\Milhq\QualificationFactory;
use PluginTests\Tests\Factories\Milhq\RankFactory;
use PluginTests\Tests\Factories\Milhq\RosterFactory;
use PluginTests\Tests\Factories\Milhq\SpecialtyFactory;
use PluginTests\Tests\Factories\Milhq\StatusFactory;
use PluginTests\Tests\Factories\Milhq\UnitFactory;
use PluginTests\Tests\Factories\Milhq\SoldierFactory;
use Zenstruck\Foundry\Story;

/**
 * This story sets up organizational resources for a standard milsim unit.
 *
 * @method static Entity\Status statusActiveDuty()
 * @method static Entity\Status statusRetired()
 * @method static Entity\Status statusPending()
 * @method static Entity\Status statusApproved()
 * @method static Entity\Status statusCivilian()
 * @method static Entity\Status statusAwol()
 * @method static Entity\Form formEnlistment()
 * @method static Entity\Unit unitFirstSquad()
 * @method static Entity\Unit unitSecondSquad()
 * @method static Entity\Unit unitCivilian()
 * @method static Entity\Roster rosterAlphaCompany()
 * @method static Entity\Position positionSquadLeader()
 * @method static Entity\Position positionTeamLeader()
 * @method static Entity\Position positionRiflemanAT()
 * @method static Entity\Position positionCivilian()
 * @method static Entity\Specialty specialtyInfantry()
 * @method static Entity\Rank rankPVT()
 * @method static Entity\Rank rankPFC()
 * @method static Entity\Rank rankSGT()
 * @method static Entity\Qualification qualificationLandNav()
 * @method static Entity\Qualification qualificationCLS()
 * @method static array<Entity\Soldier> firstSquad()
 * @method static array<Entity\Soldier> secondSquad()
 */
class MilsimStory extends Story
{
    public function __construct(
        private readonly SettingRepository $settingRepository,
        private readonly MenuItemRepository $menuItemRepository,
    ) {
    }

    public function build(): void
    {
        $this->createMenu();

        // Statuses
        $activeDuty = StatusFactory::createOne(['name' => 'Active Duty']);
        $this->addState('statusActiveDuty', $activeDuty);
        $retired = StatusFactory::createOne(['name' => 'Retired']);
        $this->addState('statusRetired', $retired);
        $pending = StatusFactory::createOne(['name' => 'Pending']);
        $this->addState('statusPending', $pending);
        $approved = StatusFactory::createOne(['name' => 'Approved']);
        $this->addState('statusApproved', $approved);
        StatusFactory::createOne(['name' => 'Denied']);
        $civilian = StatusFactory::createOne(['name' => 'Civilian']);
        $this->addState('statusCivilian', $civilian);
        $awol = StatusFactory::createOne(['name' => 'AWOL']);
        $this->addState('statusAwol', $awol);

        $this->settingRepository->set('milhq.enlistment.status', [$retired->getId()]);

        // Forms
        $enlistmentForm = FormFactory::createOne([
            'defaultStatus' => $pending,
            'instructions' => 'Enlistment Instructions',
            'name' => 'Enlistment',
            'successMessage' => 'Enlistment Success',
        ]);
        FormFieldFactory::createOne([
            'form' => $enlistmentForm,
            'key' => 'reason',
            'label' => 'Why would you like to join our unit',
            'required' => true,
        ]);

        $this->settingRepository->set('milhq.enlistment.form', $enlistmentForm->getId());
        $this->addState('formEnlistment', $enlistmentForm);

        $enlistmentForum = ForumFactory::createOne(['title' => 'Enlistments']);
        $this->settingRepository->set('milhq.enlistment.forum', $enlistmentForum->getId());

        // Positions
        $squadLeader = PositionFactory::createOne(['name' => 'Squad Leader']);
        $this->addState('positionSquadLeader', $squadLeader);
        $teamLeader = PositionFactory::createOne(['name' => 'Team Leader']);
        $this->addState('positionTeamLeader', $teamLeader);
        $riflemanAT = PositionFactory::createOne(['name' => 'Rifleman AT']);
        $this->addState('positionRiflemanAT', $riflemanAT);
        $civilianPosition = PositionFactory::createOne(['name' => 'Civilian']);
        $this->addState('positionCivilian', $civilianPosition);

        // Units
        $firstSquad = UnitFactory::createOne([
            'name' => 'First Squad',
            'supervisors' => new ArrayCollection([$squadLeader, $teamLeader]),
        ]);
        $this->addState('unitFirstSquad', $firstSquad);
        $secondSquad = UnitFactory::createOne([
            'name' => 'Second Squad',
            'supervisors' => new ArrayCollection([$squadLeader, $teamLeader]),
        ]);
        $this->addState('unitSecondSquad', $secondSquad);
        $civilianUnit = UnitFactory::createOne(['name' => 'Civilian']);
        $this->addState('unitCivilian', $civilianUnit);

        $alphaCompany = RosterFactory::createOne([
            'name' => 'Alpha Company',
            'units' => new ArrayCollection([$firstSquad, $secondSquad]),
        ]);
        $this->addState('rosterAlphaCompany', $alphaCompany);

        // Specialties
        $infantry = SpecialtyFactory::createOne(['name' => 'Infantryman', 'abbreviation' => '11B']);
        $this->addState('specialtyInfantry', $infantry);

        // Ranks
        $sgt = RankFactory::createOne(['name' => 'Sergeant', 'abbreviation' => 'SGT', 'paygrade' => 'E5']);
        $this->addState('rankSGT', $sgt);
        $cpl = RankFactory::createOne(['name' => 'Corporal', 'abbreviation' => 'CPL', 'paygrade' => 'E4']);
        $spc = RankFactory::createOne(['name' => 'Specialist', 'abbreviation' => 'SPC', 'paygrade' => 'E4']);
        $pfc = RankFactory::createOne(['name' => 'Private First Class', 'abbreviation' => 'PFC', 'paygrade' => 'E3']);
        $this->addState('rankPFC', $pfc);
        $pv2 = RankFactory::createOne(['name' => 'Private Second Class', 'abbreviation' => 'PV2', 'paygrade' => 'E2']);
        $pvt = RankFactory::createOne(['name' => 'Private Trainee', 'abbreviation' => 'PVT', 'paygrade' => 'E1']);
        $this->addState('rankPVT', $pvt);

        // Users
        $firstSquadUsers = [];
        $firstSquadUsers[] = $this->createSoldier($sgt, $firstSquad, $squadLeader, $infantry, $activeDuty);
        $firstSquadUsers[] = $this->createSoldier($cpl, $firstSquad, $teamLeader, $infantry, $activeDuty);
        $firstSquadUsers[] = $this->createSoldier($cpl, $firstSquad, $teamLeader, $infantry, $activeDuty);
        $firstSquadUsers[] = $this->createSoldier($spc, $firstSquad, $riflemanAT, $infantry, $activeDuty);
        $firstSquadUsers[] = $this->createSoldier($spc, $firstSquad, $riflemanAT, $infantry, $activeDuty);
        $firstSquadUsers[] = $this->createSoldier($pfc, $firstSquad, $riflemanAT, $infantry, $activeDuty);
        $firstSquadUsers[] = $this->createSoldier($pfc, $firstSquad, $riflemanAT, $infantry, $activeDuty);
        $firstSquadUsers[] = $this->createSoldier($pv2, $firstSquad, $riflemanAT, $infantry, $activeDuty);
        $firstSquadUsers[] = $this->createSoldier($pv2, $firstSquad, $riflemanAT, $infantry, $activeDuty);
        $this->addState('firstSquad', $firstSquadUsers);

        $secondSquadUsers = [];
        $secondSquadUsers[] = $this->createSoldier($sgt, $secondSquad, $squadLeader, $infantry, $activeDuty);
        $secondSquadUsers[] = $this->createSoldier($cpl, $secondSquad, $teamLeader, $infantry, $activeDuty);
        $secondSquadUsers[] = $this->createSoldier($cpl, $secondSquad, $teamLeader, $infantry, $activeDuty);
        $secondSquadUsers[] = $this->createSoldier($spc, $secondSquad, $riflemanAT, $infantry, $activeDuty);
        $secondSquadUsers[] = $this->createSoldier($spc, $secondSquad, $riflemanAT, $infantry, $activeDuty);
        $secondSquadUsers[] = $this->createSoldier($pfc, $secondSquad, $riflemanAT, $infantry, $activeDuty);
        $secondSquadUsers[] = $this->createSoldier($pfc, $secondSquad, $riflemanAT, $infantry, $activeDuty);
        $secondSquadUsers[] = $this->createSoldier($pv2, $secondSquad, $riflemanAT, $infantry, $activeDuty);
        $secondSquadUsers[] = $this->createSoldier($pv2, $secondSquad, $riflemanAT, $infantry, $activeDuty);
        $this->addState('secondSquad', $secondSquadUsers);

        // Qualifications
        $landNav = QualificationFactory::createOne(['name' => 'Land Navigation']);
        $this->addState('qualificationLandNav', $landNav);
        $cls = QualificationFactory::createOne(['name' => 'Combat Life Saver']);
        $this->addState('qualificationCLS', $cls);
    }

    private function createMenu(): void
    {
        $menu = new MenuItem();
        $menu->setName('MILHQ');
        $menu->setType('milhq');
        $menu->setPayload([
            'awards_active_duty' => true,
            'awards_guests' => true,
            'courses_active_duty' => true,
            'courses_guests' => true,
            'operations_active_duty' => true,
            'operations_guests' => true,
            'qualifications_active_duty' => true,
            'qualifications_guests' => true,
            'ranks_active_duty' => true,
            'ranks_guests' => true,
            'roster_active_duty' => true,
            'roster_guests' => true,
        ]);
        $this->menuItemRepository->save($menu);
    }

    private function createSoldier($rank, $unit, $position, $specialty, $status): Entity\Soldier
    {
        return SoldierFactory::createOne([
            'rank' => $rank,
            'unit' => $unit,
            'position' => $position,
            'specialty' => $specialty,
            'status' => $status,
        ]);
    }
}
