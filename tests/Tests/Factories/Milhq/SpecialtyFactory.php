<?php

declare(strict_types=1);

namespace PluginTests\Factories\Milhq;

use Forumify\Milhq\Entity\Specialty;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

class SpecialtyFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return Specialty::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'name' => self::faker()->sentence(),
        ];
    }
}
