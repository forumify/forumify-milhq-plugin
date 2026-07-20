<?php

declare(strict_types=1);

namespace PluginTests\Tests\Unit\Discord\Command;

use Forumify\Discord\Api\DTO\DiscordEmbed;
use Forumify\Discord\Api\Resource\DiscordCommandRun;
use Forumify\Discord\ForumifyDiscordPlugin;
use Forumify\Milhq\Discord\Command\UnitCommand;
use PHPUnit\Framework\Attributes\RequiresMethod;
use PluginTests\Tests\Factories\Milhq\UnitFactory;
use PluginTests\Tests\Factories\Stories\MilsimStory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

class UnitCommandTest extends KernelTestCase
{
    use Factories;

    #[RequiresMethod(ForumifyDiscordPlugin::class, 'getPluginMetadata')]
    public function testRun(): void
    {
        MilsimStory::load();
        $firstSquad = MilsimStory::firstSquad();

        $run = new DiscordCommandRun();
        $run->name = 'milhq-unit';
        $run->options = ['name' => 'First Squad'];
        $run->discordUserId = '123';

        $command = self::getContainer()->get(UnitCommand::class);
        $result = $command->run($run);

        self::assertCount(1, $result->embeds);

        $embed = $result->embeds[0];
        self::assertEquals('First Squad', $embed->title);

        $value = $this->getFieldValue($embed);
        self::assertEquals('HMMWV', $value('Vehicles'));

        $supervisors = $value('Supervisors');
        self::assertNotNull($supervisors);
        self::assertStringContainsString('SGT ' . $firstSquad[0]->getName(), $supervisors);
        self::assertStringContainsString('CPL ' . $firstSquad[1]->getName(), $supervisors);
        self::assertStringContainsString('CPL ' . $firstSquad[2]->getName(), $supervisors);
        self::assertSame(3, substr_count($supervisors, ',') + 1);

        $soldiers = $value('Soldiers');
        self::assertNotNull($soldiers);
        $lines = explode("\n", $soldiers);
        self::assertCount(count($firstSquad), $lines);
        self::assertStringContainsString('**SGT ' . $firstSquad[0]->getName() . '** Squad Leader', $soldiers);
        self::assertStringContainsString('**CPL ' . $firstSquad[1]->getName() . '** Team Leader', $soldiers);
    }

    #[RequiresMethod(ForumifyDiscordPlugin::class, 'getPluginMetadata')]
    public function testRunMatchesExactDesignation(): void
    {
        UnitFactory::createOne([
            'name' => 'Some Unrelated Name',
            'designation' => '1-1A',
        ]);

        $run = new DiscordCommandRun();
        $run->name = 'milhq-unit';
        $run->options = ['name' => '1-1A'];
        $run->discordUserId = '123';

        $command = self::getContainer()->get(UnitCommand::class);
        $result = $command->run($run);

        self::assertCount(1, $result->embeds);
        self::assertEquals('Some Unrelated Name', $result->embeds[0]->title);
    }

    #[RequiresMethod(ForumifyDiscordPlugin::class, 'getPluginMetadata')]
    public function testRunNoMatch(): void
    {
        $run = new DiscordCommandRun();
        $run->name = 'milhq-unit';
        $run->options = ['name' => 'Nonexistent Unit'];
        $run->discordUserId = '123';

        $command = self::getContainer()->get(UnitCommand::class);
        $result = $command->run($run);

        self::assertEmpty($result->embeds);
        self::assertNotEmpty($result->content);
    }

    private function getFieldValue(DiscordEmbed $embed): callable
    {
        return function (string $name) use ($embed): ?string {
            foreach ($embed->fields ?? [] as $field) {
                if ($field['name'] === $name) {
                    return $field['value'];
                }
            }
            return null;
        };
    }
}
