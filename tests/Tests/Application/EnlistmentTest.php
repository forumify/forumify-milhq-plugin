<?php

declare(strict_types=1);

namespace PluginTests\Tests\Application;

use Forumify\Core\Repository\SettingRepository;
use Forumify\Core\Repository\UserRepository;
use Forumify\Milhq\Repository\FormSubmissionRepository;
use Forumify\Milhq\Repository\SoldierRepository;
use PluginTests\Tests\Factories\Forumify\RoleFactory;
use PluginTests\Tests\Factories\Milhq\SoldierFactory;
use PluginTests\Tests\Factories\Stories\MilsimStory;

class EnlistmentTest extends MilhqWebTestCase
{
    public function testEnlistNewUser(): void
    {
        $role = RoleFactory::createOne(['title' => 'enlistee']);
        self::getContainer()->get(SettingRepository::class)->setBulk([
            'milhq.enlistment.role' => $role->getId(),
        ]);

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
            'soldier' => $soldier,
        ]);
        self::assertNotNull($submission);

        $this->client->request('GET', '/milhq/enlist');
        self::assertAnySelectorTextContains('p', 'Your enlistment is being processed');
        self::assertAnySelectorTextSame('a', 'View enlistment topic');
        self::assertAnySelectorTextSame('a', 'Start another enlistment');

        $user = self::getContainer()->get(UserRepository::class)->find($this->user->getId());
        self::assertNotEmpty($user->getRoleEntities()->filter(fn ($r) => $r->getTitle() === 'enlistee'));
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
