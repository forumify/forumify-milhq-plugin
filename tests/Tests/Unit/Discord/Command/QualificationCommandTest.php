<?php

declare(strict_types=1);

namespace PluginTests\Tests\Unit\Discord\Command;

use Forumify\Discord\Api\Resource\DiscordCommandRun;
use Forumify\Discord\ForumifyDiscordPlugin;
use Forumify\Milhq\Discord\Command\QualificationCommand;
use PHPUnit\Framework\Attributes\RequiresMethod;
use PluginTests\Tests\Factories\Milhq\QualificationFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

class QualificationCommandTest extends KernelTestCase
{
    use Factories;

    #[RequiresMethod(ForumifyDiscordPlugin::class, 'getPluginMetadata')]
    public function testRun(): void
    {
        QualificationFactory::createOne([
            'name' => 'Combat Lifesaver',
            'description' => '<p>Basic <strong>first aid</strong> training.</p>',
            'image' => 'qualifications/cls.png',
        ]);
        QualificationFactory::createOne(['name' => 'Marksman']);

        $run = new DiscordCommandRun();
        $run->name = 'milhq-qualification';
        $run->options = ['name' => 'Combat Lifesaver'];
        $run->discordUserId = '123';

        $command = self::getContainer()->get(QualificationCommand::class);
        $result = $command->run($run);

        self::assertCount(1, $result->embeds);

        $embed = $result->embeds[0];
        self::assertEquals('Combat Lifesaver', $embed->title);
        self::assertEquals('Basic **first aid** training.', $embed->description);
        self::assertNotEmpty($embed->thumbnail['url'] ?? null);
    }

    #[RequiresMethod(ForumifyDiscordPlugin::class, 'getPluginMetadata')]
    public function testRunNoMatch(): void
    {
        $run = new DiscordCommandRun();
        $run->name = 'milhq-qualification';
        $run->options = ['name' => 'Nonexistent Qualification'];
        $run->discordUserId = '123';

        $command = self::getContainer()->get(QualificationCommand::class);
        $result = $command->run($run);

        self::assertEmpty($result->embeds);
        self::assertNotEmpty($result->content);
    }
}
