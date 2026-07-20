<?php

declare(strict_types=1);

namespace PluginTests\Tests\Unit\Discord\Command;

use Forumify\Discord\Api\Resource\DiscordCommandRun;
use Forumify\Discord\ForumifyDiscordPlugin;
use Forumify\Milhq\Discord\Command\AwardCommand;
use PHPUnit\Framework\Attributes\RequiresMethod;
use PluginTests\Tests\Factories\Milhq\AwardFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

class AwardCommandTest extends KernelTestCase
{
    use Factories;

    #[RequiresMethod(ForumifyDiscordPlugin::class, 'getPluginMetadata')]
    public function testRun(): void
    {
        AwardFactory::createOne([
            'name' => 'Medal of Honor',
            'description' => '<h1>Description</h1><p>Awarded for <strong><u>extreme</u></strong> bravery.</p><img src="foo.png">',
            'image' => 'awards/medal-of-honor.png',
        ]);
        AwardFactory::createOne(['name' => 'Purple Heart']);

        $run = new DiscordCommandRun();
        $run->name = 'milhq-award';
        $run->options = ['name' => 'Medal of Honor'];
        $run->discordUserId = '123';

        $command = self::getContainer()->get(AwardCommand::class);
        $result = $command->run($run);

        self::assertCount(1, $result->embeds);

        $embed = $result->embeds[0];
        self::assertEquals('Medal of Honor', $embed->title);
        self::assertEquals("# Description\nAwarded for ***extreme*** bravery.", $embed->description);
        self::assertNotEmpty($embed->thumbnail['url'] ?? null);
    }

    #[RequiresMethod(ForumifyDiscordPlugin::class, 'getPluginMetadata')]
    public function testRunNoMatch(): void
    {
        $run = new DiscordCommandRun();
        $run->name = 'milhq-award';
        $run->options = ['name' => 'Nonexistent Award'];
        $run->discordUserId = '123';

        $command = self::getContainer()->get(AwardCommand::class);
        $result = $command->run($run);

        self::assertEmpty($result->embeds);
        self::assertNotEmpty($result->content);
    }

    #[RequiresMethod(ForumifyDiscordPlugin::class, 'getPluginMetadata')]
    public function testRunLimitsAndSortsResults(): void
    {
        AwardFactory::createOne(['name' => 'Test Award Charlie']);
        AwardFactory::createOne(['name' => 'Test Award Alpha']);
        AwardFactory::createOne(['name' => 'Test Award Echo']);
        AwardFactory::createOne(['name' => 'Test Award Bravo']);
        AwardFactory::createOne(['name' => 'Test Award Delta']);
        AwardFactory::createOne(['name' => 'Test Award Foxtrot']);

        $run = new DiscordCommandRun();
        $run->name = 'milhq-award';
        $run->options = ['name' => 'Test Award'];
        $run->discordUserId = '123';

        $command = self::getContainer()->get(AwardCommand::class);
        $result = $command->run($run);

        self::assertCount(5, $result->embeds);

        // Awards are sorted by position, which follows creation order here,
        // and the last created award ("Foxtrot") should be excluded by the limit of 5.
        $titles = array_map(static fn ($embed) => $embed->title, $result->embeds);
        self::assertSame([
            'Test Award Charlie',
            'Test Award Alpha',
            'Test Award Echo',
            'Test Award Bravo',
            'Test Award Delta',
        ], $titles);
    }
}
