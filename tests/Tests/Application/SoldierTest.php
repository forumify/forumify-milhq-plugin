<?php

declare(strict_types=1);

namespace PluginTests\Tests\Application;

use PluginTests\Tests\Factories\Stories\MilsimStory;

class SoldierTest extends MilhqWebTestCase
{
    public function testSoldier(): void
    {
        $soldier = MilsimStory::firstSquad()[5];

        $this->client->request('get', '/milhq/soldier/' . $soldier->getId());

        self::assertResponseIsSuccessful();

        self::assertSelectorTextContains('#specialty', $soldier->getSpecialty()->getName());
        self::assertSelectorTextContains('#unit', $soldier->getUnit()->getName());
        self::assertSelectorTextContains('#position', $soldier->getPosition()->getName());
        self::assertSelectorCount(3, '#supervisors > li');
    }
}
