<?php

declare(strict_types=1);

namespace PluginTests\Tests\Application\Api;

use DateTime;
use Forumify\Milhq\Entity\Record\RankRecord;
use Forumify\Milhq\Entity\Soldier;
use Forumify\Testing\Api\ApiTestCase;
use Forumify\Testing\Api\Crud\DeleteTestTrait;
use Forumify\Testing\Api\Crud\GetCollectionTestTrait;
use Forumify\Testing\Api\Crud\GetTestTrait;
use Forumify\Testing\Api\Crud\PatchTestTrait;
use Forumify\Testing\Api\Crud\PostTestTrait;
use PluginTests\Tests\Factories\Milhq\RankFactory;
use PluginTests\Tests\Factories\Milhq\Record\AssignmentRecordFactory;
use PluginTests\Tests\Factories\Milhq\SoldierFactory;
use PluginTests\Tests\Factories\Milhq\StatusFactory;
use PluginTests\Tests\Factories\Milhq\UnitFactory;

use function Zenstruck\Foundry\Persistence\persist;

class SoldierApiTest extends ApiTestCase
{
    use GetCollectionTestTrait;
    use GetTestTrait;
    use PostTestTrait;
    use PatchTestTrait;
    use DeleteTestTrait;

    protected static function factory(): string
    {
        return SoldierFactory::class;
    }

    protected static function endpoint(): string
    {
        return '/api/milhq/soldiers';
    }

    protected function getPostBody(): array
    {
        return ['name' => 'John Doe'];
    }

    protected function getPatchBody(): array
    {
        return ['name' => 'Jane Doe'];
    }

    public function testGetEmbedsSoldierSummary(): void
    {
        $rank = RankFactory::createOne(['name' => 'Sergeant', 'abbreviation' => 'SGT', 'image' => 'sgt.png']);
        $unit = UnitFactory::createOne(['name' => 'First Squad']);
        $status = StatusFactory::createOne(['name' => 'Active Duty', 'color' => '#00ff00']);
        $soldier = SoldierFactory::createOne(['rank' => $rank, 'unit' => $unit, 'status' => $status]);

        $response = $this->get(self::endpoint() . "/{$soldier->getId()}");

        self::assertSame('Sergeant', $response['rank']['name']);
        self::assertSame('SGT', $response['rank']['abbreviation']);
        self::assertSame('/storage/milhq/sgt.png', $response['rank']['image']);
        self::assertArrayNotHasKey('description', $response['rank']);
        self::assertSame('First Squad', $response['unit']['name']);
        self::assertSame('#00ff00', $response['status']['color']);
    }

    public function testGetIncludesTimeInServiceAndGrade(): void
    {
        $status = StatusFactory::createOne(['freezeTimeInService' => true]);
        $soldier = SoldierFactory::createOne(['createdAt' => new DateTime('-20 days')]);
        $this->addRankRecord($soldier, 10);
        AssignmentRecordFactory::createOne([
            'soldier' => $soldier,
            'status' => $status,
            'createdAt' => new DateTime('-5 days'),
        ]);

        $response = $this->get(self::endpoint() . "/{$soldier->getId()}");

        self::assertSame(['days' => 15, 'frozen' => true], $response['timeInService']);
        self::assertSame(['days' => 10, 'frozen' => false], $response['timeInGrade']);
    }

    public function testGetOmitsTimeInGradeWithoutRankRecord(): void
    {
        $soldier = SoldierFactory::createOne(['createdAt' => new DateTime('-20 days')]);

        $response = $this->get(self::endpoint() . "/{$soldier->getId()}");

        self::assertSame(['days' => 20, 'frozen' => false], $response['timeInService']);
        self::assertArrayNotHasKey('timeInGrade', $response);
    }

    public function testCollectionIncludesTimesForEverySoldier(): void
    {
        $status = StatusFactory::createOne(['freezeTimeInGrade' => true]);
        $frozen = SoldierFactory::createOne(['createdAt' => new DateTime('-20 days')]);
        $this->addRankRecord($frozen, 20);
        AssignmentRecordFactory::createOne([
            'soldier' => $frozen,
            'status' => $status,
            'createdAt' => new DateTime('-5 days'),
        ]);
        $active = SoldierFactory::createOne(['createdAt' => new DateTime('-8 days')]);
        $this->addRankRecord($active, 3);

        $members = array_column($this->getCollection(self::endpoint()), null, 'id');

        self::assertSame(['days' => 20, 'frozen' => false], $members[$frozen->getId()]['timeInService']);
        self::assertSame(['days' => 15, 'frozen' => true], $members[$frozen->getId()]['timeInGrade']);
        self::assertSame(['days' => 8, 'frozen' => false], $members[$active->getId()]['timeInService']);
        self::assertSame(['days' => 3, 'frozen' => false], $members[$active->getId()]['timeInGrade']);
    }

    public function testTimesAreNotEmbeddedInRecords(): void
    {
        $soldier = SoldierFactory::createOne(['createdAt' => new DateTime('-20 days')]);
        $record = AssignmentRecordFactory::createOne(['soldier' => $soldier, 'status' => StatusFactory::createOne()]);

        $response = $this->get("/api/milhq/assignment-records/{$record->getId()}");

        self::assertArrayNotHasKey('timeInService', $response['soldier']);
    }

    public function testPatchResponseDoesNotIncludeTimes(): void
    {
        $soldier = SoldierFactory::createOne();

        $response = $this->patch(self::endpoint() . "/{$soldier->getId()}", ['json' => ['name' => 'Jane Doe']]);

        self::assertArrayNotHasKey('timeInService', $response);
    }

    private function addRankRecord(Soldier $soldier, int $daysAgo): void
    {
        persist(RankRecord::class, [
            'soldier' => $soldier,
            'rank' => RankFactory::createOne(),
            'type' => 'promotion',
            'createdAt' => new DateTime("-$daysAgo days"),
        ]);
    }

    public function testPostRequiresName(): void
    {
        self::assertSame(422, $this->request('POST', self::endpoint())->getStatusCode());
        self::assertSame(422, $this->request('POST', self::endpoint(), ['json' => ['name' => '']])->getStatusCode());
    }

    public function testAssignmentCanOnlyChangeThroughAssignmentRecords(): void
    {
        $unit = UnitFactory::createOne(['name' => 'First Squad']);
        $soldier = SoldierFactory::createOne(['unit' => $unit]);

        $response = $this->patch(self::endpoint() . "/{$soldier->getId()}", [
            'json' => ['unit' => '/api/milhq/units/' . UnitFactory::createOne()->getId()],
        ]);

        self::assertSame('First Squad', $response['unit']['name']);
    }

    public function testPatchAssignsRelationsByIri(): void
    {
        $rank = RankFactory::createOne();
        $soldier = SoldierFactory::createOne();

        $response = $this->patch(self::endpoint() . "/{$soldier->getId()}", [
            'json' => ['rank' => "/api/milhq/ranks/{$rank->getId()}"],
        ]);

        self::assertSame($rank->getId(), $response['rank']['id']);
    }
}
