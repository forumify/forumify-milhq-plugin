<?php

declare(strict_types=1);

namespace PluginTests\Tests\Unit\Service;

use Forumify\Core\Repository\SettingRepository;
use Forumify\Milhq\Service\SyncSoldierService;
use League\Flysystem\FilesystemOperator;
use PluginTests\Tests\Factories\Milhq\SoldierFactory;
use PluginTests\Tests\Factories\Stories\MilsimStory;
use PluginTests\Tests\Traits\UserTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

class SyncSoldierServiceTest extends KernelTestCase
{
    use Factories;
    use UserTrait;

    public function testSync(): void
    {
        self::getContainer()->get(SettingRepository::class)->setBulk([
            'milhq.profile.overwrite_display_names' => true,
            'milhq.profile.overwrite_signatures' => true,
            'milhq.profile.overwrite_avatars' => true,
        ]);

        MilsimStory::load();

        /** @var FilesystemOperator $milhqFs */
        $milhqFs = self::getContainer()->get('milhq_asset.storage');
        $milhqFs->write('rank.png', TEST_DATA_DIR . '/sergeant.png');

        $sgt = MilsimStory::rankSGT();
        $sgt->setImage('rank.png');

        $user = $this->createUser();
        SoldierFactory::createOne([
            'name' => 'Blippy Bloppy',
            'user' => $user,
            'rank' => $sgt,
            'status' => MilsimStory::statusActiveDuty(),
            'signature' => 'blippy-bloppy.png',
        ]);

        self::getContainer()->get(SyncSoldierService::class)->sync($user->getId());

        self::assertEquals('SGT Blippy Bloppy', $user->getDisplayName());
        self::assertStringContainsString('src="/storage/milhq/blippy-bloppy.png"', $user->getSignature());
        self::assertStringContainsString('sergeant.png', $user->getAvatar());
    }
}
