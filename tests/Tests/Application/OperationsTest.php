<?php

declare(strict_types=1);

namespace PluginTests\Tests\Application;

use DateInterval;
use DateTimeImmutable;
use Forumify\Core\Repository\SettingRepository;
use Forumify\Calendar\ForumifyCalendarPlugin;
use Forumify\Milhq\Repository\AfterActionReportRepository;
use Forumify\Milhq\Entity\Operation;
use Forumify\Testing\Factories\Core\ACLFactory;
use Forumify\Testing\Factories\Core\UserFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresMethod;
use PluginTests\Tests\Factories\Forumify\CalendarFactory;
use PluginTests\Tests\Factories\Milhq\AfterActionReportFactory;
use PluginTests\Tests\Factories\Milhq\OperationFactory;
use PluginTests\Tests\Factories\Milhq\SoldierFactory;
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
        foreach ($present as $presentUser) {
            $cr = $presentUser->getCombatRecords()->first();
            self::assertNotNull($cr);
            self::assertEquals('Operation Sandstorm: Mission Test Mission', $cr->getText());
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
            'attendance' => ['present' => [$present->getId()], 'absent' => [$absent->getId()]],
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
}
