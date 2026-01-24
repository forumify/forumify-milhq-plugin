<?php

declare(strict_types=1);

namespace PluginTests\Factories\Milhq;

use Forumify\Milhq\Entity\Unit;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

class UnitFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return Unit::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'name' => self::faker()->sentence(),
        ];
    }
}
