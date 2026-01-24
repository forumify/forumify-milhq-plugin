<?php

declare(strict_types=1);

namespace PluginTests\Factories\Milhq;

use Forumify\Milhq\Entity\Qualification;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

class QualificationFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return Qualification::class;
    }

    protected function defaults(): array|callable
    {
        return [];
    }
}
