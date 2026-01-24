<?php

declare(strict_types=1);

namespace PluginTests\Application;

use Forumify\Core\Entity\User;
use PluginTests\Factories\Stories\MilsimStory;
use PluginTests\Traits\UserTrait;
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
