<?php

declare(strict_types=1);

namespace PluginTests\Tests\Unit\Migrations;

use DateTime;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Version\Version;
use Doctrine\ORM\EntityManagerInterface;
use ForumifyMilhqPluginMigrations\Version20261018083412;
use Forumify\Milhq\Entity\AfterActionReportAttendance;
use Forumify\Milhq\Entity\Record\CombatRecord;
use PluginTests\Tests\Factories\Milhq\AfterActionReportFactory;
use PluginTests\Tests\Factories\Milhq\MissionFactory;
use PluginTests\Tests\Factories\Milhq\OperationFactory;
use PluginTests\Tests\Factories\Milhq\Record\CombatRecordFactory;
use PluginTests\Tests\Factories\Milhq\SoldierFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

class Version20261018083412Test extends KernelTestCase
{
    use Factories;

    public function testBackfillLinksCombatRecordsToAfterActionReports(): void
    {
        [$quick, $shifted, $shiftedOtherText, $absent, $alreadyLinked] = SoldierFactory::createMany(5);
        $mission = MissionFactory::createOne([
            'operation' => OperationFactory::createOne(['title' => 'Sandstorm']),
            'title' => 'Day One',
        ]);
        $aar = AfterActionReportFactory::createOne([
            'mission' => $mission,
            'createdAt' => new DateTime('2026-01-01 12:00:00'),
            'attendance' => [
                new AfterActionReportAttendance($quick, 'present'),
                new AfterActionReportAttendance($shifted, 'present'),
                new AfterActionReportAttendance($shiftedOtherText, 'present'),
                new AfterActionReportAttendance($absent, 'absent'),
                new AfterActionReportAttendance($alreadyLinked, 'present'),
            ],
        ]);
        $otherAar = AfterActionReportFactory::createOne();

        $quickRecord = CombatRecordFactory::createOne(['soldier' => $quick, 'text' => 'Renamed since', 'createdAt' => new DateTime('2026-01-01 12:00:02')]);
        $shiftedRecord = CombatRecordFactory::createOne(['soldier' => $shifted, 'text' => 'Operation Sandstorm: Mission Day One', 'createdAt' => new DateTime('2026-01-01 15:00:01')]);
        $shiftedOtherTextRecord = CombatRecordFactory::createOne(['soldier' => $shiftedOtherText, 'text' => 'Manual record', 'createdAt' => new DateTime('2026-01-01 15:00:01')]);
        $absentRecord = CombatRecordFactory::createOne(['soldier' => $absent, 'createdAt' => new DateTime('2026-01-01 12:00:01')]);
        $alreadyLinkedRecord = CombatRecordFactory::createOne(['soldier' => $alreadyLinked, 'afterActionReport' => $otherAar, 'createdAt' => new DateTime('2026-01-01 12:00:01')]);

        $this->runBackfill();

        self::assertSame($aar->getId(), $this->findLinkedAfterActionReportId($quickRecord->getId()));
        self::assertSame($aar->getId(), $this->findLinkedAfterActionReportId($shiftedRecord->getId()));
        self::assertNull($this->findLinkedAfterActionReportId($shiftedOtherTextRecord->getId()));
        self::assertNull($this->findLinkedAfterActionReportId($absentRecord->getId()));
        self::assertSame($otherAar->getId(), $this->findLinkedAfterActionReportId($alreadyLinkedRecord->getId()));
    }

    public function testBackfillPicksClosestAfterActionReport(): void
    {
        $soldier = SoldierFactory::createOne();
        $mission = MissionFactory::createOne();
        $firstAar = AfterActionReportFactory::createOne([
            'mission' => $mission,
            'createdAt' => new DateTime('2026-01-01 12:00:00'),
            'attendance' => [new AfterActionReportAttendance($soldier, 'present')],
        ]);
        $secondAar = AfterActionReportFactory::createOne([
            'mission' => $mission,
            'createdAt' => new DateTime('2026-01-01 12:00:30'),
            'attendance' => [new AfterActionReportAttendance($soldier, 'present')],
        ]);
        $firstRecord = CombatRecordFactory::createOne(['soldier' => $soldier, 'createdAt' => new DateTime('2026-01-01 12:00:01')]);
        $secondRecord = CombatRecordFactory::createOne(['soldier' => $soldier, 'createdAt' => new DateTime('2026-01-01 12:00:31')]);

        $this->runBackfill();

        self::assertSame($firstAar->getId(), $this->findLinkedAfterActionReportId($firstRecord->getId()));
        self::assertSame($secondAar->getId(), $this->findLinkedAfterActionReportId($secondRecord->getId()));
    }

    private function runBackfill(): void
    {
        $migration = self::getContainer()
            ->get('doctrine.migrations.dependency_factory')
            ->getMigrationRepository()
            ->getMigration(new Version(Version20261018083412::class))
            ->getMigration();
        $migration->up(new Schema());
        $queries = $migration->getSql();

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->getConnection()->executeStatement(end($queries)->getStatement());
        $entityManager->clear();
    }

    private function findLinkedAfterActionReportId(int $recordId): ?int
    {
        return self::getContainer()
            ->get(EntityManagerInterface::class)
            ->find(CombatRecord::class, $recordId)
            ?->getAfterActionReport()
            ?->getId();
    }
}
