<?php

declare(strict_types=1);

namespace PluginTests\Tests\Factories\Milhq;

use Forumify\Milhq\Entity\AwardTier;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

class AwardTierFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return AwardTier::class;
    }

    protected function defaults(): array|callable
    {
        return [];
    }
}
