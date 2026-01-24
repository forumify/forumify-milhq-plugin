<?php

declare(strict_types=1);

namespace PluginTests\Application;

use Forumify\Milhq\Entity\Soldier;
use Forumify\Milhq\Repository\SoldierRepository;
use PluginTests\Factories\Milhq\SoldierFactory;
use PluginTests\Factories\Stories\MilsimStory;
use PluginTests\Traits\UserTrait;
use Zenstruck\Foundry\Test\Factories;

class RankRecordTest extends MilhqWebTestCase
{
    use Factories;
    use UserTrait;

    public function testCreateRankRecord(): void
    {
        $targetUser = SoldierFactory::createOne();

        $c = $this->client->request('GET', '/admin/milhq/records/rank');
        $newRecordLink = $c->filter('a[aria-label="New rank record"]')->link();
        $this->client->click($newRecordLink);

        $this->client->submitForm('Save', [
            'record[soldiers]' => [$targetUser->getId()],
            'record[type]' => 'promotion',
            'record[rank]' => MilsimStory::rankPVT()->getId(),
        ]);

        self::assertResponseIsSuccessful();

        /** @var Soldier $soldier */
        $soldier = self::getContainer()->get(SoldierRepository::class)->find($targetUser->getId());
        self::assertEquals('Private Trainee', $soldier->getRank()->getName());
    }
}
