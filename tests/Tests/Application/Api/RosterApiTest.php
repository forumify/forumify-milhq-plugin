<?php

declare(strict_types=1);

namespace PluginTests\Tests\Application\Api;

use Forumify\Testing\Api\ApiTestCase;
use Forumify\Testing\Api\Crud\DeleteTestTrait;
use Forumify\Testing\Api\Crud\GetCollectionTestTrait;
use Forumify\Testing\Api\Crud\GetTestTrait;
use Forumify\Testing\Api\Crud\PatchTestTrait;
use Forumify\Testing\Api\Crud\PostTestTrait;
use PluginTests\Tests\Factories\Milhq\PositionFactory;
use PluginTests\Tests\Factories\Milhq\Record\AssignmentRecordFactory;
use PluginTests\Tests\Factories\Milhq\RosterFactory;
use PluginTests\Tests\Factories\Stories\MilsimStory;

class RosterApiTest extends ApiTestCase
{
    use GetCollectionTestTrait;
    use GetTestTrait;
    use PostTestTrait;
    use PatchTestTrait;
    use DeleteTestTrait;

    protected static function factory(): string
    {
        return RosterFactory::class;
    }

    protected static function endpoint(): string
    {
        return '/api/milhq/rosters';
    }

    protected function getPostBody(): array
    {
        return ['name' => 'Bravo Company'];
    }

    protected function getPatchBody(): array
    {
        return ['name' => 'Charlie Company'];
    }

    public function testGetRosterUnitsWithSoldiers(): void
    {
        MilsimStory::load();
        $roster = MilsimStory::rosterAlphaCompany();

        $units = $this->getCollection("/api/milhq/rosters/{$roster->getId()}/units");

        self::assertSame(['First Squad', 'Second Squad'], array_map(fn (array $u) => $u['unit']['name'], $units));

        $firstSquad = $units[0];
        self::assertTrue($firstSquad['markSupervisorsOnRoster']);
        self::assertSame(['Squad Leader', 'Team Leader'], array_column($firstSquad['supervisors'], 'name'));
        self::assertCount(count(MilsimStory::firstSquad()), $firstSquad['soldiers']);

        $leader = $firstSquad['soldiers'][0];
        self::assertSame('Sergeant', $leader['rank']['name']);
        self::assertSame('Squad Leader', $leader['position']['name']);
        self::assertSame('Infantryman', $leader['specialty']['name']);
        self::assertSame('Active Duty', $leader['status']['name']);
    }

    public function testSecondaryAssignmentsAppearWithTheirPosition(): void
    {
        MilsimStory::load();
        $soldier = MilsimStory::firstSquad()[5];
        $medic = PositionFactory::createOne(['name' => 'Combat Medic']);
        AssignmentRecordFactory::createOne([
            'soldier' => $soldier,
            'type' => 'secondary',
            'unit' => MilsimStory::unitSecondSquad(),
            'position' => $medic,
        ]);

        $units = $this->getCollection('/api/milhq/rosters/' . MilsimStory::rosterAlphaCompany()->getId() . '/units');

        $secondSquad = array_column($units[1]['soldiers'], null, 'id');
        self::assertArrayHasKey($soldier->getId(), $secondSquad);
        self::assertSame('Combat Medic', $secondSquad[$soldier->getId()]['position']['name']);
    }
}
