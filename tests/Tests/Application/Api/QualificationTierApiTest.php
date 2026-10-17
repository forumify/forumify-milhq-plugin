<?php

declare(strict_types=1);

namespace PluginTests\Tests\Application\Api;

use Forumify\Testing\Api\ApiTestCase;
use Forumify\Testing\Api\Crud\DeleteTestTrait;
use Forumify\Testing\Api\Crud\GetTestTrait;
use Forumify\Testing\Api\Crud\PatchTestTrait;
use PluginTests\Tests\Factories\Milhq\QualificationFactory;
use PluginTests\Tests\Factories\Milhq\QualificationTierFactory;

class QualificationTierApiTest extends ApiTestCase
{
    use GetTestTrait;
    use PatchTestTrait;
    use DeleteTestTrait;

    protected static function factory(): string
    {
        return QualificationTierFactory::class;
    }

    protected static function endpoint(): string
    {
        return '/api/milhq/qualification-tiers';
    }

    protected function getPatchBody(): array
    {
        return ['name' => 'Expert'];
    }

    public function testTiersOfParent(): void
    {
        $parent = QualificationFactory::createOne();

        $tier = $this->post("/api/milhq/qualifications/{$parent->getId()}/tiers", [
            'json' => ['name' => 'Silver'],
        ]);
        QualificationTierFactory::createOne();

        $tiers = $this->getCollection("/api/milhq/qualifications/{$parent->getId()}/tiers");
        self::assertSame([$tier['id']], array_column($tiers, 'id'));
        self::assertSame(['Silver'], array_column($this->get("/api/milhq/qualifications/{$parent->getId()}")['tiers'], 'name'));
    }
}
