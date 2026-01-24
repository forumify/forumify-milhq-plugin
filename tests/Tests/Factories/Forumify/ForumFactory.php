<?php

declare(strict_types=1);

namespace PluginTests\Factories\Forumify;

use Forumify\Forum\Entity\Forum;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

class ForumFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return Forum::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'title' => self::faker()->sentence(),
        ];
    }
}
