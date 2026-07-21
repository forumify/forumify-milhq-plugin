<?php

declare(strict_types=1);

namespace PluginTests\Tests\Factories\Milhq;

use Forumify\Milhq\Entity\Award;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

class AwardFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return Award::class;
    }

    protected function defaults(): array|callable
    {
        return [];
    }
}
