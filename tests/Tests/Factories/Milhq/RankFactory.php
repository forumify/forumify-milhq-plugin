<?php

declare(strict_types=1);

namespace PluginTests\Tests\Factories\Milhq;

use Forumify\Milhq\Entity\Rank;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

class RankFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return Rank::class;
    }

    protected function defaults(): array|callable
    {
        return [];
    }
}
