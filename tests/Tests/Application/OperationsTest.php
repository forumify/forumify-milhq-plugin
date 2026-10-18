<?php

declare(strict_types=1);

namespace PluginTests\Tests\Application;

use DateInterval;
use DateTime;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Forumify\Core\Repository\SettingRepository;
use Forumify\Calendar\ForumifyCalendarPlugin;
use Forumify\Milhq\Entity\AfterActionReport;
use Forumify\Milhq\Entity\AfterActionReportAttendance;
use Forumify\Milhq\Entity\Record\CombatRecord;
use Forumify\Milhq\Entity\Soldier;
use Forumify\Milhq\Repository\AfterActionReportRepository;
use Forumify\Milhq\Entity\Operation;
use Forumify\Milhq\Repository\AssignmentRecordRepository;
use Forumify\Milhq\Repository\CombatRecordRepository;
use Forumify\Testing\Factories\Core\ACLFactory;
use Forumify\Testing\Factories\Core\UserFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresMethod;
use PluginTests\Tests\Factories\Forumify\CalendarFactory;
use PluginTests\Tests\Factories\Milhq\AfterActionReportFactory;
use PluginTests\Tests\Factories\Milhq\MissionFactory;
use PluginTests\Tests\Factories\Milhq\OperationFactory;
use PluginTests\Tests\Factories\Milhq\Record\CombatRecordFactory;
use PluginTests\Tests\Factories\Milhq\SoldierFactory;
use PluginTests\Tests\Factories\Milhq\StatusFactory;
use PluginTests\Tests\Factories\Milhq\UnitFactory;
use PluginTests\Tests\Factories\Stories\MilsimStory;
use PluginTests\Tests\Traits\SessionTrait;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

class OperationsTest extends MilhqWebTestCase
{
    use InteractsWithLiveComponents;
    use SessionTrait;

    public function testOperationToAttendance(): void
    {
        self::getContainer()->get(SettingRepository::class)->set('milhq.operations.absent_notification', true);

        SoldierFactory::createOne(['user' => $this->user, 'status' => MilsimStory::statusActiveDuty()]);

        $c = $this->client->request('GET', '/admin/milhq/operations');
        $this->client->click($c->filter('a[aria-label="New Operation"]')->link());

        $this->client->submitForm('Save', [
            'operation[title]' => 'Sandstorm',
            'operation[description]' => 'Operation description',
            'operation[content]' => '<p>Operation content</p>',
            'operation[start]' => '2025-01-01',
            'operation[requestRsvp]' => true,
            'operation[missionBriefingTemplate]' => 'Briefing template',
            'operation[afterActionReportTemplate]' => 'After action report template',
        ]);
        // ACLs
        $this->client->submitForm('Save');

        $this->client->request('GET', '/milhq/operations/sandstorm');
        self::assertSelectorTextSame('h1', 'Sandstorm');

        $c = $this->client->clickLink('New Mission');
        $initialBriefingText = $c->filter('#mission_briefing')->innerText();
        self::assertSame('Briefing template', $initialBriefingText);

        $now = new DateTimeImmutable();
        $start = $now->add(new DateInterval('P1D'));
        $end = $now->add(new DateInterval('P1DT1H'));

        $c = $this->client->submitForm('Save', [
            'mission[title]' => 'Test Mission',
            'mission[start]' => $start->format('Y-m-d H:i:s'),
            'mission[end]' => $end->format('Y-m-d H:i:s'),
            'mission[sendNotification]' => true,
            'mission[createCombatRecords]' => true,
            'mission[briefing]' => 'Mission briefing',
        ]);

        self::assertSelectorTextSame('h1', 'Test Mission');
        self::assertAnySelectorTextContains('button', 'RSVP');

        $c = $this->client->clickLink('New After Action Report');
        $initialAarText = $c->filter('#after_action_report_report')->innerText();
        self::assertSame('After action report template', $initialAarText);

        $unit = MilsimStory::unitFirstSquad();

        $i = 0;
        $attendances = ['present', 'present', 'present', 'excused', 'excused', 'excused', 'absent', 'absent', 'absent'];
        $attendanceJson = [];
        $present = [];
        foreach ($unit->getSoldiers() as $unitUser) {
            $attendance = $attendances[$i++];
            $attendanceJson[$attendance][] = $unitUser->getId();
            if ($attendance === 'present') {
                $present[] = $unitUser;
            }
        }
        self::assertCount(3, $present);

        $c = $this->client->submitForm('Save', [
            'after_action_report[unit]' => $unit->getId(),
            'after_action_report[report]' => '<span id="test-aar-report">After action report content</span>',
            'after_action_report[attendanceJson]' => json_encode($attendanceJson),
        ]);

        self::assertSelectorExists('#test-aar-report');
        $aarId = (int)basename($this->client->getRequest()->getPathInfo());
        foreach ($present as $presentUser) {
            $cr = $presentUser->getCombatRecords()->first();
            self::assertNotNull($cr);
            self::assertEquals('Operation Sandstorm: Mission Test Mission', $cr->getText());
            self::assertSame($aarId, $cr->getAfterActionReport()?->getId());
        }

        $this->initializeSession();
        $c = $this
            ->createLiveComponent('Milhq\\AttendanceSheet')
            ->actingAs($this->user)
            ->submitForm([
                'form' => [
                    'from' => $now->sub(new DateInterval('P3D'))->format('Y-m-d'),
                    'to' => $now->add(new DateInterval('P3D'))->format('Y-m-d'),
                ],
            ], 'calculate')
            ->render()
            ->crawler()
        ;

        $value = $c->filter('td[data-testid="total-present"]')->siblings()->first()->innerText();
        self::assertEquals(3, $value);
        $value = $c->filter('td[data-testid="total-excused"]')->siblings()->first()->innerText();
        self::assertEquals(3, $value);
        $value = $c->filter('td[data-testid="total-absent"]')->siblings()->first()->innerText();
        self::assertEquals(3, $value);
        $value = $c->filter('td[data-testid="perc-attended"]')->siblings()->first()->innerText();
        self::assertEquals('33%', $value);
        $value = $c->filter('td[data-testid="perc-accountable"]')->siblings()->first()->innerText();
        self::assertEquals('66%', $value);
    }

    #[RequiresMethod(ForumifyCalendarPlugin::class, 'getPluginMetadata')]
    public function testMissionCreatesCalendarEvent(): void
    {
        $operation = OperationFactory::createOne();
        $calendar = CalendarFactory::createOne(['title' => 'Missions']);

        $this->client->request('GET', "/milhq/operations/{$operation->getSlug()}");
        $this->client->clickLink('New Mission');

        $now = new DateTimeImmutable();
        $start = $now->add(new DateInterval('P1D'));
        $end = $now->add(new DateInterval('P1DT1H'));

        $this->client->submitForm('Save', [
            'mission[title]' => 'Test Mission',
            'mission[start]' => $start->format('Y-m-d H:i:s'),
            'mission[end]' => $end->format('Y-m-d H:i:s'),
            'mission[calendar]' => $calendar->getId(),
            'mission[sendNotification]' => true,
            'mission[createCombatRecords]' => true,
            'mission[briefing]' => 'Mission briefing',
        ]);

        $calendar = CalendarFactory::find($calendar->getId());
        self::assertCount(1, $calendar->getEvents());
    }

    public function testViewerCanSeeAfterActionReportWithoutManageButtons(): void
    {
        $present = SoldierFactory::createOne(['name' => 'John Present']);
        $absent = SoldierFactory::createOne(['name' => 'Jane Absent']);
        $aar = AfterActionReportFactory::createOne([
            'report' => '<p id="aar-report">We won.</p>',
            'attendance' => [
                new AfterActionReportAttendance($present, 'present'),
                new AfterActionReportAttendance($absent, 'absent'),
            ],
        ]);
        ACLFactory::createOne([
            'entity' => Operation::class,
            'entityId' => (string)$aar->getMission()->getOperation()->getId(),
            'permission' => 'view_after_action_reports',
        ]);
        $this->client->loginUser(UserFactory::createOne());

        $this->client->request('GET', "/milhq/aar/{$aar->getId()}");

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('#aar-report');
        self::assertSelectorTextContains('table.box tbody', 'John Present');
        self::assertSelectorTextContains('table.box tbody', 'Jane Absent');
        self::assertCount(1, $this->client->getCrawler()->filter('table.box tbody tr'));
        self::assertSelectorNotExists("a[href*='/milhq/aar/{$aar->getId()}/edit']");
        self::assertSelectorNotExists("a[href*='/milhq/aar/{$aar->getId()}/delete']");
    }

    public function testManagerSeesManageButtons(): void
    {
        $aar = AfterActionReportFactory::createOne();

        $this->client->request('GET', "/milhq/aar/{$aar->getId()}");

        self::assertSelectorExists("a[href*='/milhq/aar/{$aar->getId()}/edit']");
        self::assertSelectorExists("a[href*='/milhq/aar/{$aar->getId()}/delete']");
    }

    public function testViewingAfterActionReportRequiresViewAccess(): void
    {
        $aar = AfterActionReportFactory::createOne();
        $this->client->loginUser(UserFactory::createOne());

        $this->client->request('GET', "/milhq/aar/{$aar->getId()}");

        self::assertResponseStatusCodeSame(403);
    }

    #[DataProvider('aarManagementActions')]
    public function testAuthorWithoutAccessCannotManageAfterActionReport(string $action): void
    {
        $author = UserFactory::createOne();
        $aarId = AfterActionReportFactory::createOne(['createdBy' => $author, 'report' => 'Original report'])->getId();
        $this->client->loginUser($author);

        $this->client->request('GET', "/milhq/aar/{$aarId}/{$action}?confirmed=1");

        self::assertResponseStatusCodeSame(403);
        $aar = self::getContainer()->get(AfterActionReportRepository::class)->find($aarId);
        self::assertNotNull($aar);
        self::assertSame('Original report', $aar->getReport());
    }

    /**
     * @return iterable<array{string}>
     */
    public static function aarManagementActions(): iterable
    {
        yield 'edit' => ['edit'];
        yield 'delete' => ['delete'];
    }

    public function testEditingAfterActionReportUpdatesAttendance(): void
    {
        [$stays, $leaves, $joins] = SoldierFactory::createMany(3);
        $aar = AfterActionReportFactory::createOne([
            'report' => 'Original report',
            'attendance' => [
                new AfterActionReportAttendance($stays, 'present'),
                new AfterActionReportAttendance($leaves, 'absent'),
            ],
        ]);
        $staysEntryId = $aar->getAttendance()->first()->getId();

        $c = $this->client->request('GET', "/milhq/aar/{$aar->getId()}/edit");
        self::assertSame(
            ['present' => [$stays->getId()], 'absent' => [$leaves->getId()]],
            json_decode($c->filter('#after_action_report_attendanceJson')->attr('value'), true),
        );

        $this->client->submitForm('Save', [
            'after_action_report[attendanceJson]' => json_encode([
                'excused' => [(string)$stays->getId()],
                'present' => [(string)$joins->getId()],
            ]),
        ]);

        self::assertResponseIsSuccessful();
        $attendance = $this->findAttendance($aar->getId());
        self::assertSame([$stays->getId() => 'excused', $joins->getId() => 'present'], $attendance);
        $staysEntry = $this->findAfterActionReport($aar->getId())->getAttendance()->first();
        self::assertSame($staysEntryId, $staysEntry->getId());
    }

    public function testDeletingSoldierRemovesTheirAttendance(): void
    {
        $deleted = SoldierFactory::createOne(['name' => 'John Deleted']);
        $kept = SoldierFactory::createOne(['name' => 'Jane Kept']);
        $aar = AfterActionReportFactory::createOne([
            'attendance' => [
                new AfterActionReportAttendance($deleted, 'present'),
                new AfterActionReportAttendance($kept, 'absent'),
            ],
        ]);

        $em = self::getContainer()->get(EntityManagerInterface::class);
        $em->remove($em->find(Soldier::class, $deleted->getId()));
        $em->flush();
        $em->clear();

        self::assertSame([$kept->getId() => 'absent'], $this->findAttendance($aar->getId()));

        $this->client->request('GET', "/milhq/aar/{$aar->getId()}");
        self::assertSelectorTextContains('table.box tbody', 'Jane Kept');
        self::assertSelectorTextNotContains('table.box tbody', 'John Deleted');
    }

    public function testConsecutiveAbsenceStatusOnlyAppliesToRepeatAbsentees(): void
    {
        $status = StatusFactory::createOne(['name' => 'AWOL']);
        $settings = self::getContainer()->get(SettingRepository::class);
        $settings->set('milhq.operations.consecutive_absent_notification', true);
        $settings->set('milhq.operations.consecutive_absent_notification_count', 2);
        $settings->set('milhq.operations.consecutive_absent_notification_message', 'Report to your CO');
        $settings->set('milhq.operations.consecutive_absent_status', $status->getId());

        $unit = UnitFactory::createOne();
        $repeatAbsentee = SoldierFactory::createOne(['unit' => $unit]);
        $firstAbsence = SoldierFactory::createOne(['unit' => $unit]);
        $operation = OperationFactory::createOne();

        AfterActionReportFactory::createOne([
            'unit' => $unit,
            'mission' => MissionFactory::createOne(['operation' => $operation, 'start' => new DateTime('-2 weeks')]),
            'attendance' => [
                new AfterActionReportAttendance($repeatAbsentee, 'absent'),
                new AfterActionReportAttendance($firstAbsence, 'present'),
            ],
        ]);
        $mission = MissionFactory::createOne(['operation' => $operation, 'start' => new DateTime('-1 week')]);

        $this->client->request('GET', "/milhq/aar/new?mission={$mission->getId()}");
        $this->client->submitForm('Save', [
            'after_action_report[unit]' => $unit->getId(),
            'after_action_report[report]' => 'Nobody showed up',
            'after_action_report[attendanceJson]' => json_encode([
                'absent' => [$repeatAbsentee->getId(), $firstAbsence->getId()],
            ]),
        ]);

        $assignmentRecords = self::getContainer()->get(AssignmentRecordRepository::class);
        self::assertCount(1, $assignmentRecords->findBy(['soldier' => $repeatAbsentee->getId(), 'status' => $status->getId()]));
        self::assertCount(0, $assignmentRecords->findBy(['soldier' => $firstAbsence->getId(), 'status' => $status->getId()]));
    }

    public function testAttendanceSheetCountsSoldiersWhoChangedUnits(): void
    {
        $operation = OperationFactory::createOne();
        $firstMission = MissionFactory::createOne(['operation' => $operation, 'start' => new DateTime('-2 days')]);
        $secondMission = MissionFactory::createOne(['operation' => $operation, 'start' => new DateTime('-1 day')]);

        $oldUnit = UnitFactory::createOne();
        $newUnit = UnitFactory::createOne();
        $stayed = SoldierFactory::createOne(['unit' => $oldUnit]);
        $transferred = SoldierFactory::createOne(['unit' => $newUnit]);

        AfterActionReportFactory::createOne([
            'unit' => $oldUnit,
            'mission' => $firstMission,
            'attendance' => [
                new AfterActionReportAttendance($stayed, 'absent'),
                new AfterActionReportAttendance($transferred, 'present'),
            ],
        ]);
        AfterActionReportFactory::createOne([
            'unit' => $newUnit,
            'mission' => $secondMission,
            'attendance' => [new AfterActionReportAttendance($transferred, 'excused')],
        ]);

        $this->initializeSession();
        $c = $this
            ->createLiveComponent('Milhq\\AttendanceSheet')
            ->actingAs($this->user)
            ->submitForm([
                'form' => [
                    'from' => (new DateTime('-1 week'))->format('Y-m-d'),
                    'to' => (new DateTime())->format('Y-m-d'),
                    'operation' => [$operation->getId()],
                ],
            ], 'calculate')
            ->render()
            ->crawler()
        ;

        $totals = fn (string $testId) => $c->filter("td[data-testid=\"$testId\"]")->siblings()->each(fn ($td) => $td->innerText());
        self::assertSame(['1', '0'], $totals('total-present'));
        self::assertSame(['0', '1'], $totals('total-excused'));
        self::assertSame(['1', '0'], $totals('total-absent'));
        self::assertSame(['50%', '0%'], $totals('perc-attended'));
    }

    public function testEditingAfterActionReportSyncsCombatRecords(): void
    {
        [$stays, $leaves, $joins] = SoldierFactory::createMany(3);
        $aar = AfterActionReportFactory::createOne([
            'report' => 'Original report',
            'mission' => MissionFactory::createOne(['createCombatRecords' => true, 'combatRecordText' => 'Took the hill']),
            'attendance' => [
                new AfterActionReportAttendance($stays, 'present'),
                new AfterActionReportAttendance($leaves, 'present'),
                new AfterActionReportAttendance($joins, 'absent'),
            ],
        ]);
        $staysRecord = CombatRecordFactory::createOne(['soldier' => $stays, 'afterActionReport' => $aar, 'text' => 'Took the hill']);
        CombatRecordFactory::createOne(['soldier' => $leaves, 'afterActionReport' => $aar, 'text' => 'Took the hill']);
        $unrelatedRecord = CombatRecordFactory::createOne(['soldier' => $leaves, 'text' => 'Earlier deployment']);

        $this->client->request('GET', "/milhq/aar/{$aar->getId()}/edit");
        $this->client->submitForm('Save', [
            'after_action_report[attendanceJson]' => json_encode([
                'present' => [(string)$stays->getId(), (string)$joins->getId()],
                'excused' => [(string)$leaves->getId()],
            ]),
        ]);

        self::assertResponseIsSuccessful();
        $records = $this->findCombatRecords($aar->getId());
        self::assertEqualsCanonicalizing([$stays->getId(), $joins->getId()], array_keys($records));
        self::assertSame($staysRecord->getId(), $records[$stays->getId()]->getId());
        self::assertSame('Took the hill', $records[$joins->getId()]->getText());
        self::assertNotNull(self::getContainer()->get(CombatRecordRepository::class)->find($unrelatedRecord->getId()));
    }

    public function testEditingAfterActionReportWithoutCombatRecordsLeavesRecordsAlone(): void
    {
        $soldier = SoldierFactory::createOne();
        $aar = AfterActionReportFactory::createOne([
            'report' => 'Original report',
            'mission' => MissionFactory::createOne(['createCombatRecords' => false]),
            'attendance' => [new AfterActionReportAttendance($soldier, 'absent')],
        ]);

        $this->client->request('GET', "/milhq/aar/{$aar->getId()}/edit");
        $this->client->submitForm('Save', [
            'after_action_report[attendanceJson]' => json_encode(['present' => [(string)$soldier->getId()]]),
        ]);

        self::assertResponseIsSuccessful();
        self::assertSame([], $this->findCombatRecords($aar->getId()));
        self::assertCount(0, self::getContainer()->get(CombatRecordRepository::class)->findBy(['soldier' => $soldier->getId()]));
    }

    public function testDeletingAfterActionReportDeletesItsCombatRecords(): void
    {
        $soldier = SoldierFactory::createOne();
        $aar = AfterActionReportFactory::createOne([
            'mission' => MissionFactory::createOne(['createCombatRecords' => true]),
            'attendance' => [new AfterActionReportAttendance($soldier, 'present')],
        ]);
        $aarId = $aar->getId();
        CombatRecordFactory::createOne(['soldier' => $soldier, 'afterActionReport' => $aar]);
        $unrelatedRecord = CombatRecordFactory::createOne(['soldier' => $soldier]);

        $this->client->request('GET', "/milhq/aar/{$aarId}/delete?confirmed=1");

        self::assertResponseIsSuccessful();
        self::assertSame([], $this->findCombatRecords($aarId));
        $remaining = self::getContainer()->get(CombatRecordRepository::class)->findBy(['soldier' => $soldier->getId()]);
        self::assertSame([$unrelatedRecord->getId()], array_map(fn ($record) => $record->getId(), $remaining));
    }

    /**
     * @return array<int, CombatRecord>
     */
    private function findCombatRecords(int $aarId): array
    {
        self::getContainer()->get(EntityManagerInterface::class)->clear();

        $records = [];
        foreach (self::getContainer()->get(CombatRecordRepository::class)->findBy(['afterActionReport' => $aarId]) as $record) {
            $records[$record->getSoldier()->getId()] = $record;
        }
        return $records;
    }

    /**
     * @return array<int, string>
     */
    private function findAttendance(int $aarId): array
    {
        $attendance = [];
        foreach ($this->findAfterActionReport($aarId)->getAttendance() as $entry) {
            $attendance[$entry->getSoldier()->getId()] = $entry->getState();
        }
        return $attendance;
    }

    private function findAfterActionReport(int $aarId): AfterActionReport
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $em->clear();
        return $em->find(AfterActionReport::class, $aarId);
    }
}
