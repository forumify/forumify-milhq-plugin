<?php

declare(strict_types=1);

namespace PluginTests\Tests\Application;

use DateTime;
use Forumify\Milhq\Entity\Record\AssignmentRecord;
use Forumify\Milhq\Entity\Record\RankRecord;
use Forumify\Milhq\Entity\Soldier;
use Forumify\Milhq\Entity\Status;
use Forumify\Milhq\Service\SoldierService;
use PluginTests\Tests\Factories\Milhq\RankFactory;
use PluginTests\Tests\Factories\Milhq\Record\AssignmentRecordFactory;
use PluginTests\Tests\Factories\Milhq\SoldierFactory;
use PluginTests\Tests\Factories\Milhq\StatusFactory;

use function Zenstruck\Foundry\Persistence\persist;

class SoldierTimeFreezeTest extends MilhqWebTestCase
{
    public function testTimeInServiceIsUnchangedWithoutFreezingStatuses(): void
    {
        $soldier = $this->createSoldier(20);
        $this->addStatus($soldier, StatusFactory::createOne(), 10);

        self::assertSame('20 days', $this->timeInService($soldier));
    }

    public function testFrozenPeriodIsExcludedFromTimeInService(): void
    {
        $active = StatusFactory::createOne();
        $leave = StatusFactory::createOne(['freezeTimeInService' => true]);

        $soldier = $this->createSoldier(20);
        $this->addStatus($soldier, $active, 20);
        $this->addStatus($soldier, $leave, 15);
        $this->addStatus($soldier, $active, 10);

        self::assertSame('15 days', $this->timeInService($soldier));
    }

    public function testCurrentFrozenStatusStopsTheClock(): void
    {
        $soldier = $this->createSoldier(20);
        $this->addStatus($soldier, StatusFactory::createOne(), 20);
        $this->addStatus($soldier, StatusFactory::createOne(['freezeTimeInService' => true]), 8);

        self::assertSame('12 days', $this->timeInService($soldier));
    }

    public function testRepeatedFrozenRecordsAreOnlyCountedOnce(): void
    {
        $leave = StatusFactory::createOne(['freezeTimeInService' => true]);

        $soldier = $this->createSoldier(20);
        $this->addStatus($soldier, $leave, 10);
        $this->addStatus($soldier, $leave, 5);

        self::assertSame('10 days', $this->timeInService($soldier));
    }

    public function testFlagsAreIndependent(): void
    {
        $status = StatusFactory::createOne(['freezeTimeInGrade' => true]);

        $soldier = $this->createSoldier(20);
        $this->addRank($soldier, 20);
        $this->addStatus($soldier, $status, 5);

        self::assertSame('20 days', $this->timeInService($soldier));
        self::assertSame('15 days', $this->timeInGrade($soldier));
    }

    public function testStatusFromBeforeLastRankRecordOnlyFreezesFromThatRankRecord(): void
    {
        $active = StatusFactory::createOne();
        $leave = StatusFactory::createOne(['freezeTimeInGrade' => true]);

        $soldier = $this->createSoldier(30);
        $this->addStatus($soldier, $leave, 25);
        $this->addRank($soldier, 12);
        $this->addStatus($soldier, $active, 10);

        self::assertSame('10 days', $this->timeInGrade($soldier));
    }

    public function testStatusRecordsBeforeSoldierStartAreIgnored(): void
    {
        $leave = StatusFactory::createOne(['freezeTimeInService' => true]);

        $soldier = $this->createSoldier(20);
        $this->addStatus($soldier, $leave, 100);
        $this->addStatus($soldier, StatusFactory::createOne(), 15);

        self::assertSame('15 days', $this->timeInService($soldier));
    }

    public function testTimeBeforeFirstStatusRecordCountsAsActive(): void
    {
        $soldier = $this->createSoldier(20);
        $this->addStatus($soldier, StatusFactory::createOne(['freezeTimeInService' => true]), 5);

        self::assertSame('15 days', $this->timeInService($soldier));
    }

    public function testBackdatedRecordDoesNotOverrideCurrentStatusForOpenPeriod(): void
    {
        $civilian = StatusFactory::createOne(['freezeTimeInService' => true]);
        $awol = StatusFactory::createOne();

        $soldier = $this->createSoldier(20);
        $this->addStatus($soldier, $awol, 5);
        $this->addStatus($soldier, $civilian, 6);

        self::assertSame($civilian->getId(), $soldier->getStatus()?->getId());
        self::assertSame('14 days', $this->timeInService($soldier));
    }

    public function testSecondaryAssignmentStatusIsIgnored(): void
    {
        $leave = StatusFactory::createOne(['freezeTimeInService' => true]);

        $soldier = $this->createSoldier(20);
        AssignmentRecordFactory::createOne([
            'soldier' => $soldier,
            'status' => $leave,
            'type' => AssignmentRecord::TYPE_SECONDARY,
            'createdAt' => new DateTime('-10 days'),
        ]);

        self::assertSame('20 days', $this->timeInService($soldier));
    }

    public function testFullyFrozenTimeInGradeShowsZeroDays(): void
    {
        $soldier = $this->createSoldier(20);
        $this->addStatus($soldier, StatusFactory::createOne(['freezeTimeInGrade' => true]), 15);
        $this->addRank($soldier, 10);

        self::assertSame('0 days', $this->timeInGrade($soldier));
    }

    public function testProfileShowsFrozenIconOnlyForFrozenTimes(): void
    {
        $status = StatusFactory::createOne(['name' => 'On Leave', 'freezeTimeInService' => true]);

        $soldier = $this->createSoldier(20);
        $this->addRank($soldier, 20);
        $this->addStatus($soldier, $status, 5);

        $crawler = $this->client->request('GET', '/milhq/soldier/' . $soldier->getId());

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('On Leave', (string)$crawler->filter('#tis-frozen')->attr('title'));
        self::assertSelectorNotExists('#tig-frozen');
    }

    public function testProfileHidesFrozenIconWithoutFrozenStatus(): void
    {
        $soldier = $this->createSoldier(20);
        $this->addRank($soldier, 20);
        $this->addStatus($soldier, StatusFactory::createOne(), 5);

        $this->client->request('GET', '/milhq/soldier/' . $soldier->getId());

        self::assertResponseIsSuccessful();
        self::assertSelectorNotExists('#tis-frozen');
        self::assertSelectorNotExists('#tig-frozen');
    }

    private function timeInService(Soldier $soldier): string
    {
        $service = self::getContainer()->get(SoldierService::class);

        return $service->formatTimeInX($service->getTimeInService($soldier));
    }

    private function timeInGrade(Soldier $soldier): string
    {
        $service = self::getContainer()->get(SoldierService::class);

        return $service->formatTimeInX($service->getTimeInGrade($soldier));
    }

    private function createSoldier(int $servedDays): Soldier
    {
        return SoldierFactory::createOne(['createdAt' => new DateTime("-$servedDays days")]);
    }

    private function addStatus(Soldier $soldier, Status $status, int $daysAgo): void
    {
        AssignmentRecordFactory::createOne([
            'soldier' => $soldier,
            'status' => $status,
            'createdAt' => new DateTime("-$daysAgo days"),
        ]);
    }

    private function addRank(Soldier $soldier, int $daysAgo): void
    {
        persist(RankRecord::class, [
            'soldier' => $soldier,
            'rank' => RankFactory::createOne(),
            'type' => 'promotion',
            'createdAt' => new DateTime("-$daysAgo days"),
        ]);
    }
}
