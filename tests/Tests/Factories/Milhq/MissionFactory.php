<?php

declare(strict_types=1);

namespace PluginTests\Tests\Factories\Milhq;

use DateTime;
use Forumify\Milhq\Entity\Mission;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Mission>
 */
class MissionFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return Mission::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'operation' => OperationFactory::new(),
            'title' => self::faker()->sentence(3),
            'start' => new DateTime('+1 week'),
        ];
    }
}
