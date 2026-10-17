<?php

declare(strict_types=1);

namespace PluginTests\Tests\Factories\Milhq;

use Forumify\Milhq\Entity\RankGroup;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<RankGroup>
 */
class RankGroupFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return RankGroup::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'title' => self::faker()->words(2, true),
        ];
    }
}
