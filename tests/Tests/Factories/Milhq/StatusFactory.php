<?php

declare(strict_types=1);

namespace PluginTests\Factories\Milhq;

use Forumify\Milhq\Entity\Status;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

class StatusFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return Status::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'color' => self::faker()->hexColor(),
        ];
    }
}
