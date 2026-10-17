<?php

declare(strict_types=1);

namespace PluginTests\Tests\Application\Api;

use Forumify\Testing\Api\ApiTestCase;
use Forumify\Testing\Api\Crud\DeleteTestTrait;
use Forumify\Testing\Api\Crud\GetCollectionTestTrait;
use Forumify\Testing\Api\Crud\GetTestTrait;
use Forumify\Testing\Api\Crud\PatchTestTrait;
use Forumify\Testing\Api\Crud\PostTestTrait;
use PluginTests\Tests\Factories\Milhq\RankFactory;
use PluginTests\Tests\Factories\Milhq\SoldierFactory;
use PluginTests\Tests\Factories\Milhq\StatusFactory;
use PluginTests\Tests\Factories\Milhq\UnitFactory;

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
