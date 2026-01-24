<?php

declare(strict_types=1);

namespace PluginTests\Factories\Milhq;

use Forumify\Milhq\Entity\Soldier;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Soldier>
 */
class SoldierFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return Soldier::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'name' => self::faker()->firstNameMale() . ' ' . self::faker()->lastName(),
        ];
    }
}
