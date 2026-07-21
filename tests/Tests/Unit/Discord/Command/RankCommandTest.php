<?php

declare(strict_types=1);

namespace PluginTests\Tests\Unit\Discord\Command;

use Forumify\Discord\Api\Resource\DiscordCommandRun;
use Forumify\Discord\ForumifyDiscordPlugin;
use Forumify\Milhq\Discord\Command\RankCommand;
use PHPUnit\Framework\Attributes\RequiresMethod;
use PluginTests\Tests\Factories\Milhq\RankFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

class RankCommandTest extends KernelTestCase
{
    use Factories;

    #[RequiresMethod(ForumifyDiscordPlugin::class, 'getPluginMetadata')]
    public function testRun(): void
    {
        RankFactory::createOne([
            'name' => 'Sergeant',
            'abbreviation' => 'SGT',
            'paygrade' => 'E5',
            'description' => '<p>Leads a squad.</p>',
            'image' => 'ranks/sergeant.png',
        ]);
        RankFactory::createOne(['name' => 'Corporal']);

        $run = new DiscordCommandRun();
        $run->name = 'milhq-rank';
        $run->options = ['name' => 'Sergeant'];
        $run->discordUserId = '123';

        $command = self::getContainer()->get(RankCommand::class);
        $result = $command->run($run);

        self::assertCount(1, $result->embeds);

        $embed = $result->embeds[0];
        self::assertEquals('Sergeant', $embed->title);
        self::assertEquals('Leads a squad.', $embed->description);
        self::assertNotEmpty($embed->thumbnail['url'] ?? null);

        $value = $this->getFieldValue($embed->fields ?? []);
        self::assertEquals('SGT', $value('Abbreviation'));
        self::assertEquals('E5', $value('Paygrade'));
    }

    #[RequiresMethod(ForumifyDiscordPlugin::class, 'getPluginMetadata')]
    public function testRunNoMatch(): void
    {
        $run = new DiscordCommandRun();
        $run->name = 'milhq-rank';
        $run->options = ['name' => 'Nonexistent Rank'];
        $run->discordUserId = '123';

        $command = self::getContainer()->get(RankCommand::class);
        $result = $command->run($run);

        self::assertEmpty($result->embeds);
        self::assertNotEmpty($result->content);
    }

    /**
     * @param array<array{name: string, value: string}> $fields
     */
    private function getFieldValue(array $fields): callable
    {
        return function (string $name) use ($fields): ?string {
            foreach ($fields as $field) {
                if ($field['name'] === $name) {
                    return $field['value'];
                }
            }
            return null;
        };
    }
}
