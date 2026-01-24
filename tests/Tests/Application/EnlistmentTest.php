<?php

declare(strict_types=1);

namespace PluginTests\Application;

use Forumify\Milhq\Repository\FormSubmissionRepository;
use Forumify\Milhq\Repository\SoldierRepository;
use PluginTests\Factories\Milhq\SoldierFactory;
use PluginTests\Factories\Stories\MilsimStory;

class EnlistmentTest extends MilhqWebTestCase
{
    public function testEnlistNewUser(): void
    {
        $this->client->request('GET', '/');
        $this->client->clickLink('Enlist');

        self::assertAnySelectorTextContains('div.rich-text', 'Enlistment Instructions');

        $this->client->submitForm('Enlist', [
            'enlistment[additionalFormData][reason]' => 'I am pro gamer!',
            'enlistment[firstName]' => 'John',
            'enlistment[lastName]' => 'Doe',
        ]);

        self::assertAnySelectorTextSame('div.rich-text', 'Enlistment Success');

        $soldier = self::getContainer()->get(SoldierRepository::class)->findOneBy(['user' => $this->user]);
        self::assertNotNull($soldier);

        $submission = self::getContainer()->get(FormSubmissionRepository::class)->findOneBy([
            'form' => MilsimStory::formEnlistment()->getId(),
            'user' => $soldier,
        ]);
        self::assertNotNull($submission);

        $this->client->request('GET', '/soldier/enlist');
        self::assertAnySelectorTextContains('p', 'Your enlistment is being processed');
        self::assertAnySelectorTextSame('a', 'View enlistment topic');
        self::assertAnySelectorTextSame('a', 'Start another enlistment');
    }

    public function testEnlistExistingUser(): void
    {
        SoldierFactory::createOne([
            'status' => MilsimStory::statusRetired(),
            'user' => $this->user,
        ]);

        $this->client->request('GET', '/');
        $this->client->clickLink('Enlist');
        $this->client->clickLink('Start another enlistment');
        $this->client->submitForm('Enlist', [
            'enlistment[additionalFormData][reason]' => 'I am pro gamer!',
            'enlistment[firstName]' => 'John',
            'enlistment[lastName]' => 'Doe',
        ]);

        self::assertAnySelectorTextSame('div.rich-text', 'Enlistment Success');
    }
}
