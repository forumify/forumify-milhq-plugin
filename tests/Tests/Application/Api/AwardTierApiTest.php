<?php

declare(strict_types=1);

namespace PluginTests\Tests\Application\Api;

use Forumify\Testing\Api\ApiTestCase;
use Forumify\Testing\Api\Crud\DeleteTestTrait;
use Forumify\Testing\Api\Crud\GetTestTrait;
use Forumify\Testing\Api\Crud\PatchTestTrait;
use PluginTests\Tests\Factories\Milhq\AwardFactory;
use PluginTests\Tests\Factories\Milhq\AwardTierFactory;

class AwardTierApiTest extends ApiTestCase
{
    use GetTestTrait;
    use PatchTestTrait;
    use DeleteTestTrait;

    protected static function factory(): string
    {
        return AwardTierFactory::class;
    }

    protected static function endpoint(): string
    {
        return '/api/milhq/award-tiers';
    }

    protected function getPatchBody(): array
    {
        return ['name' => 'Gold'];
    }

    public function testTiersOfParent(): void
    {
        $parent = AwardFactory::createOne();

        $tier = $this->post("/api/milhq/awards/{$parent->getId()}/tiers", [
            'json' => ['name' => 'Silver'],
        ]);
        AwardTierFactory::createOne();

        $tiers = $this->getCollection("/api/milhq/awards/{$parent->getId()}/tiers");
        self::assertSame([$tier['id']], array_column($tiers, 'id'));
        self::assertSame(['Silver'], array_column($this->get("/api/milhq/awards/{$parent->getId()}")['tiers'], 'name'));
    }
}
