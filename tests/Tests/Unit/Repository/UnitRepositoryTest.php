<?php

declare(strict_types=1);

namespace PluginTests\Tests\Unit\Repository;

use Forumify\Milhq\Entity\Soldier;
use Forumify\Milhq\Repository\UnitRepository;
use PluginTests\Tests\Factories\Milhq\SoldierFactory;
use PluginTests\Tests\Factories\Stories\MilsimStory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

class UnitRepositoryTest extends KernelTestCase
{
    use Factories;

    public function testFindBySoldierIsSupervisor(): void
    {
        MilsimStory::load();

        /** @var UnitRepository $unitRepository */
        $unitRepository = self::getContainer()->get(UnitRepository::class);

        $squadLeader = SoldierFactory::createOne([
            'unit' => MilsimStory::unitFirstSquad(),
            'position' => MilsimStory::positionSquadLeader(),
        ]);

        $units = $unitRepository->findBySoldierIsSupervisor($squadLeader);
        $unitNames = array_map(static fn ($unit) => $unit->getName(), $units);

        self::assertCount(1, $units);
        self::assertEquals(['First Squad'], $unitNames);
    }

    public function testFindBySoldierIsSupervisorAcceptsId(): void
    {
        MilsimStory::load();

        /** @var UnitRepository $unitRepository */
        $unitRepository = self::getContainer()->get(UnitRepository::class);

        /** @var Soldier $teamLeader */
        $teamLeader = SoldierFactory::createOne([
            'unit' => MilsimStory::unitSecondSquad(),
            'position' => MilsimStory::positionTeamLeader(),
        ]);

        $units = $unitRepository->findBySoldierIsSupervisor($teamLeader->getId());

        self::assertCount(1, $units);
        self::assertEquals('Second Squad', $units[0]->getName());
    }

    public function testFindBySoldierIsSupervisorReturnsEmptyForNonSupervisor(): void
    {
        MilsimStory::load();

        /** @var UnitRepository $unitRepository */
        $unitRepository = self::getContainer()->get(UnitRepository::class);

        $rifleman = SoldierFactory::createOne([
            'unit' => MilsimStory::unitFirstSquad(),
            'position' => MilsimStory::positionRiflemanAT(),
        ]);

        $units = $unitRepository->findBySoldierIsSupervisor($rifleman);

        self::assertCount(0, $units);
    }
}
