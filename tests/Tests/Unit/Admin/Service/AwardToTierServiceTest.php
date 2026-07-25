<?php

declare(strict_types=1);

namespace PluginTests\Tests\Unit\Admin\Service;

use Forumify\Milhq\Admin\Service\AwardToTierService;
use PluginTests\Tests\Factories\Milhq\AwardFactory;
use PluginTests\Tests\Factories\Milhq\Record\AwardRecordFactory;
use PluginTests\Tests\Factories\Milhq\SoldierFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

class AwardToTierServiceTest extends KernelTestCase
{
    use Factories;

    public function testAwardToTier(): void
    {
        $awardA = AwardFactory::createOne(['name' => 'Marksmanship: Expert']);
        $awardB = AwardFactory::createOne(['name' => 'Marksmanship: Sharpshooter']);

        $soldier = SoldierFactory::createOne();
        $recordA = AwardRecordFactory::createOne(['soldier' => $soldier, 'award' => $awardA]);
        $recordB = AwardRecordFactory::createOne(['soldier' => $soldier, 'award' => $awardB]);

        self::getContainer()->get(AwardToTierService::class)->awardToTier(
            $awardB,
            $awardA,
            ['tierName' => 'Sharpshooter', 'targetAwardName' => 'Marksmanship', 'targetTierName' => 'Expert'],
        );

        $newAwards = AwardFactory::all();
        self::assertCount(1, $newAwards);

        $award = reset($newAwards);
        self::assertNotFalse($award);
        self::assertEquals('Marksmanship', $award->getName());
        self::assertEquals(2, $award->tiers->count());

        foreach ($award->tiers as $tier) {
            self::assertContains($tier->name, ['Expert', 'Sharpshooter']);
        }

        $tiersByName = [];
        foreach ($award->tiers as $tier) {
            $tiersByName[$tier->name] = $tier;
        }
        self::assertNotEquals(
            $tiersByName['Expert']->getPosition(),
            $tiersByName['Sharpshooter']->getPosition(),
        );

        self::assertSame($award, $recordA->getAward());
        self::assertNotNull($recordA->getTier());
        self::assertEquals('Expert', $recordA->getTier()->name);

        self::assertSame($award, $recordB->getAward());
        self::assertNotNull($recordB->getTier());
        self::assertEquals('Sharpshooter', $recordB->getTier()->name);
    }
}
