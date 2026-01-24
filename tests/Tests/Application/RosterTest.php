<?php

declare(strict_types=1);

namespace PluginTests\Application;

class RosterTest extends MilhqWebTestCase
{
    public function testRoster(): void
    {
        $c = $this->client->request('GET', '/milhq/roster');
        self::assertCount(2, $c->filter('.card-title'));
        self::assertAnySelectorTextContains('.card-title', 'First Squad');
        self::assertAnySelectorTextContains('.card-title', 'Second Squad');
    }
}
