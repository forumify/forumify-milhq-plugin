<?php

declare(strict_types=1);

namespace PluginTests\Tests\Factories\Milhq;

use Forumify\Milhq\Entity\Roster;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

class RosterFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return Roster::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'name' => self::faker()->word(),
        ];
    }
}
