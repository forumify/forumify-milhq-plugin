<?php

declare(strict_types=1);

namespace PluginTests\Tests\Application\Api;

use Forumify\Testing\Api\ApiTestCase;
use Forumify\Testing\Api\Crud\DeleteTestTrait;
use Forumify\Testing\Api\Crud\GetCollectionTestTrait;
use Forumify\Testing\Api\Crud\GetTestTrait;
use Forumify\Testing\Api\Crud\PatchTestTrait;
use Forumify\Testing\Api\Crud\PostTestTrait;
use PluginTests\Tests\Factories\Milhq\EquipmentFactory;

class EquipmentApiTest extends ApiTestCase
{
    use GetCollectionTestTrait;
    use GetTestTrait;
    use PostTestTrait;
    use PatchTestTrait;
    use DeleteTestTrait;

    protected static function factory(): string
    {
        return EquipmentFactory::class;
    }

    protected static function endpoint(): string
    {
        return '/api/milhq/equipment';
    }

    protected function getPostBody(): array
    {
        return ['name' => 'M4A1', 'type' => 'primary_weapon'];
    }

    protected function getPatchBody(): array
    {
        return ['name' => 'M249'];
    }
}
