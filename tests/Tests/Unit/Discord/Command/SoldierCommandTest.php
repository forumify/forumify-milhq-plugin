<?php

declare(strict_types=1);

namespace PluginTests\Tests\Unit\Discord\Command;

use Forumify\Discord\Api\Resource\DiscordCommandRun;
use Forumify\Discord\ForumifyDiscordPlugin;
use Forumify\Milhq\Discord\Command\SoldierCommand;
use Forumify\Discord\Api\DTO\DiscordEmbed;
use PHPUnit\Framework\Attributes\RequiresMethod;
use PluginTests\Tests\Factories\Stories\MilsimStory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

class SoldierCommandTest extends KernelTestCase
{
    use Factories;

    #[RequiresMethod(ForumifyDiscordPlugin::class, 'getPluginMetadata')]
    public function testRun(): void
    {
        MilsimStory::load();

        $firstSquadSL = MilsimStory::firstSquad()[0];

        $run = new DiscordCommandRun();
        $run->name = 'milhq-soldier';
        $run->options = ['name' => $firstSquadSL->getName()];
        $run->discordUserId = '123';

        $command = self::getContainer()->get(SoldierCommand::class);
        $result = $command->run($run);

        self::assertNotEmpty($result->embeds);

        $embed = $result->embeds[0];
        self::assertEquals('SGT ' . $firstSquadSL->getName(), $embed->title);

        $value = $this->getFieldValue($embed);
        self::assertEquals('Active Duty', $value('Status'));
        self::assertEquals('E5 Sergeant (SGT)', $value('Rank'));
        self::assertEquals('11B - First Squad - Squad Leader', $value('Assignment'));
    }

    private function getFieldValue(DiscordEmbed $embed): callable
    {
        return function (string $name) use ($embed): ?string {

            foreach ($embed->fields as $field) {
                if ($field['name'] === $name) {
                    return $field['value'];
                }
            }
            return null;
        };
    }
}
