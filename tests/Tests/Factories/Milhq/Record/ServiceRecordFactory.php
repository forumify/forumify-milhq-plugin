<?php

declare(strict_types=1);

namespace PluginTests\Tests\Factories\Milhq\Record;

use Forumify\Milhq\Entity\Record\ServiceRecord;
use PluginTests\Tests\Factories\Milhq\SoldierFactory;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<ServiceRecord>
 */
class ServiceRecordFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return ServiceRecord::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'soldier' => SoldierFactory::new(),
            'text' => self::faker()->sentence(),
        ];
    }
}
