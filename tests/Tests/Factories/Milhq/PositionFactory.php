<?php

declare(strict_types=1);

namespace PluginTests\Tests\Factories\Milhq;

use Forumify\Milhq\Entity\Position;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

class PositionFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return Position::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'name' => self::faker()->sentence(),
        ];
    }
}
