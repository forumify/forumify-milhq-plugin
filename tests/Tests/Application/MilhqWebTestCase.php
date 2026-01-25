<?php

declare(strict_types=1);

namespace PluginTests\Tests\Application;

use Forumify\Core\Entity\User;
use PluginTests\Tests\Factories\Stories\MilsimStory;
use PluginTests\Tests\Traits\UserTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;

class MilhqWebTestCase extends WebTestCase
{
    use Factories;
    use UserTrait;

    protected KernelBrowser $client;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
        $this->client->followRedirects();

        MilsimStory::load();

        $this->user = $this->createAdmin();
        $this->client->loginUser($this->user);
    }
}
