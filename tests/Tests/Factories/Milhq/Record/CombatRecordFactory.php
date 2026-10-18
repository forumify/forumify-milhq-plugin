<?php

declare(strict_types=1);

namespace PluginTests\Tests\Factories\Milhq\Record;

use Forumify\Milhq\Entity\Record\CombatRecord;
use PluginTests\Tests\Factories\Milhq\SoldierFactory;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<CombatRecord>
 */
class CombatRecordFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return CombatRecord::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'soldier' => SoldierFactory::new(),
            'text' => self::faker()->sentence(),
        ];
    }
}
