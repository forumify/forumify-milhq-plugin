<?php

declare(strict_types=1);

namespace PluginTests\Tests\Unit\Admin\Service;

use Forumify\Milhq\Admin\Service\QualificationToTierService;
use PluginTests\Tests\Factories\Milhq\QualificationFactory;
use PluginTests\Tests\Factories\Milhq\Record\QualificationRecordFactory;
use PluginTests\Tests\Factories\Milhq\SoldierFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

class QualificationToTierServiceTest extends KernelTestCase
{
    use Factories;

    public function testQualificationToTier(): void
    {
        $qualificationA = QualificationFactory::createOne(['name' => 'Rifle: Expert']);
        $qualificationB = QualificationFactory::createOne(['name' => 'Rifle: Sharpshooter']);

        $soldier = SoldierFactory::createOne();
        $recordA = QualificationRecordFactory::createOne(['soldier' => $soldier, 'qualification' => $qualificationA]);
        $recordB = QualificationRecordFactory::createOne(['soldier' => $soldier, 'qualification' => $qualificationB]);

        self::getContainer()->get(QualificationToTierService::class)->qualificationToTier(
            $qualificationB,
            $qualificationA,
            ['tierName' => 'Sharpshooter', 'targetQualificationName' => 'Rifle', 'targetTierName' => 'Expert'],
        );

        $newQuals = QualificationFactory::all();
        self::assertCount(1, $newQuals);

        $qualification = reset($newQuals);
        self::assertNotFalse($qualification);
        self::assertEquals('Rifle', $qualification->getName());
        self::assertEquals(2, $qualification->tiers->count());

        foreach ($qualification->tiers as $tier) {
            self::assertContains($tier->name, ['Expert', 'Sharpshooter']);
        }

        $tiersByName = [];
        foreach ($qualification->tiers as $tier) {
            $tiersByName[$tier->name] = $tier;
        }
        self::assertNotEquals(
            $tiersByName['Expert']->getPosition(),
            $tiersByName['Sharpshooter']->getPosition(),
        );

        self::assertSame($qualification, $recordA->getQualification());
        self::assertNotNull($recordA->getTier());
        self::assertEquals('Expert', $recordA->getTier()->name);

        self::assertSame($qualification, $recordB->getQualification());
        self::assertNotNull($recordB->getTier());
        self::assertEquals('Sharpshooter', $recordB->getTier()->name);
    }
}
