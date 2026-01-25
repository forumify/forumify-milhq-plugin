<?php

declare(strict_types=1);

namespace PluginTests\Tests\Factories\Milhq;

use Forumify\Milhq\Entity\Operation;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Operation>
 */
class OperationFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return Operation::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'title' => self::faker()->sentence(3),
            'description' => self::faker()->paragraph(),
            'content' => self::faker()->paragraph(),
        ];
    }
}
