<?php

declare(strict_types=1);

namespace PluginTests\Tests\Application;

use DateInterval;
use DateTime;
use Forumify\Core\Repository\SettingRepository;
use Forumify\Milhq\Entity\Soldier;
use Forumify\Milhq\Scheduler\ReportInTaskHandler;
use PluginTests\Tests\Factories\Milhq\ReportInFactory;
use PluginTests\Tests\Factories\Milhq\SoldierFactory;
use PluginTests\Tests\Factories\Stories\MilsimStory;
use PluginTests\Tests\Traits\SessionTrait;
use Symfony\Component\DomCrawler\Test\Constraint\CrawlerAnySelectorTextContains;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

class ReportInTest extends MilhqWebTestCase
{
    use SessionTrait;
    use InteractsWithLiveComponents;

    private Soldier $soldier;

    protected function setUp(): void
    {
        parent::setUp();

        self::getContainer()->get(SettingRepository::class)->setBulk([
            'milhq.report_in.enabled' => true,
            'milhq.report_in.period' => 5,
            'milhq.report_in.warning_period' => 3,
            'milhq.report_in.enabled_status' => [MilsimStory::statusActiveDuty()->getId()],
            'milhq.report_in.failure_status' => MilsimStory::statusAwol()->getId(),
        ]);
        $this->soldier = SoldierFactory::createOne([
            'user' => $this->user,
            'status' => MilsimStory::statusActiveDuty(),
        ]);
    }

    public function testReportInWarning(): void
    {
        $this->client->request('GET', '/milhq/operations-center');
        self::assertSelectorTextContains('.tag', 'Active Duty');
        self::assertAnySelectorTextContains('button', 'Report In');

        ReportInFactory::createOne([
            'soldier' => $this->soldier,
            'lastReportInDate' => new DateTime()->sub(new DateInterval('P4D')),
        ]);
        (self::getContainer()->get(ReportInTaskHandler::class))();
        $this->client->request('GET', '/milhq/operations-center');
        self::assertEquals('Active Duty', $this->soldier->getStatus()->getName());
        $this->assertNotificationExists('You are about to be marked AWOL');
    }

    public function testReportInFailure(): void
    {
        $this->client->request('GET', '/milhq/operations-center');
        self::assertSelectorTextContains('.tag', 'Active Duty');
        self::assertAnySelectorTextContains('button', 'Report In');

        ReportInFactory::createOne([
            'soldier' => $this->soldier,
            'lastReportInDate' => new DateTime()->sub(new DateInterval('P6D')),
        ]);
        (self::getContainer()->get(ReportInTaskHandler::class))();
        $this->client->request('GET', '/milhq/operations-center');
        self::assertSelectorTextContains('.tag', 'AWOL');
        $this->assertNotificationExists('You were marked AWOL');

        $this->initializeSession();
        $this
            ->createLiveComponent('Milhq\\ReportInButton')
            ->actingAs($this->user)
            ->call('reportIn')
            ->render();

        $this->client->request('GET', '/milhq/operations-center');
        self::assertSelectorTextContains('.tag', 'Active Duty');
    }

    private function assertNotificationExists(string $text): void
    {
        $this->initializeSession();
        $c = $this
            ->createLiveComponent('Notifications')
            ->actingAs($this->user)
            ->render()
            ->crawler();

        self::assertThat($c, new CrawlerAnySelectorTextContains('p', $text));
    }
}
