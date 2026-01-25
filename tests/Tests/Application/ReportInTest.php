<?php

declare(strict_types=1);

namespace PluginTests\Tests\Application;

use DateInterval;
use DateTime;
use Forumify\Core\Repository\SettingRepository;
use Forumify\Milhq\Scheduler\ReportInTaskHandler;
use PluginTests\Tests\Factories\Milhq\ReportInFactory;
use PluginTests\Tests\Factories\Milhq\SoldierFactory;
use PluginTests\Tests\Factories\Stories\MilsimStory;
use PluginTests\Tests\Traits\SessionTrait;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

class ReportInTest extends MilhqWebTestCase
{
    use SessionTrait;
    use InteractsWithLiveComponents;

    public function testReportInWarning(): void
    {
        $s = self::getContainer()->get(SettingRepository::class);
        $s->setBulk([
            'milhq.report_in.enabled' => true,
            'milhq.report_in.period' => 5,
            'milhq.report_in.warning_period' => 3,
            'milhq.report_in.enabled_status' => [MilsimStory::statusActiveDuty()->getId()],
            'milhq.report_in.failure_status' => MilsimStory::statusAwol()->getId(),
        ]);
        $taskHandler = self::getContainer()->get(ReportInTaskHandler::class);

        $me = SoldierFactory::createOne(['user' => $this->user, 'status' => MilsimStory::statusActiveDuty()]);
        $this->client->request('GET', '/milhq/operations-center');
        self::assertSelectorTextContains('.tag', 'Active Duty');
        self::assertAnySelectorTextContains('button', 'Report In');

        ReportInFactory::createOne([
            'soldier' => $me,
            'lastReportInDate' => new DateTime()->sub(new DateInterval('P4D')),
        ]);
        ($taskHandler)();
        $this->client->request('GET', '/milhq/operations-center');
        self::assertEquals('Active Duty', $me->getStatus()->getName());
        self::assertAnySelectorTextContains('p', 'You are about to be marked AWOL');
    }

    public function testReportInFailure(): void
    {
        $s = self::getContainer()->get(SettingRepository::class);
        $s->setBulk([
            'milhq.report_in.enabled' => true,
            'milhq.report_in.period' => 5,
            'milhq.report_in.warning_period' => 3,
            'milhq.report_in.enabled_status' => [MilsimStory::statusActiveDuty()->getId()],
            'milhq.report_in.failure_status' => MilsimStory::statusAwol()->getId(),
        ]);
        $taskHandler = self::getContainer()->get(ReportInTaskHandler::class);

        $me = SoldierFactory::createOne(['user' => $this->user, 'status' => MilsimStory::statusActiveDuty()]);
        $this->client->request('GET', '/milhq/operations-center');
        self::assertSelectorTextContains('.tag', 'Active Duty');
        self::assertAnySelectorTextContains('button', 'Report In');

        ReportInFactory::createOne([
            'soldier' => $me,
            'lastReportInDate' => new DateTime()->sub(new DateInterval('P6D')),
        ]);
        ($taskHandler)();
        $this->client->request('GET', '/milhq/operations-center');
        self::assertSelectorTextContains('.tag', 'AWOL');
        self::assertAnySelectorTextContains('p', 'You were marked AWOL');

        $this->initializeSession();
        $this
            ->createLiveComponent('Milhq\\ReportInButton')
            ->actingAs($this->user)
            ->call('reportIn')
            ->render();

        $this->client->request('GET', '/milhq/operations-center');
        self::assertSelectorTextContains('.tag', 'Active Duty');
    }
}
