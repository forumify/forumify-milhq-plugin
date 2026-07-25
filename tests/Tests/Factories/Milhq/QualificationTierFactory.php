<?php

declare(strict_types=1);

namespace PluginTests\Tests\Factories\Milhq;

use Forumify\Milhq\Entity\QualificationTier;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

class QualificationTierFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return QualificationTier::class;
    }

    protected function defaults(): array|callable
    {
        return [];
    }
}
