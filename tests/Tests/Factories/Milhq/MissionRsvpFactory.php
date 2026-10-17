<?php

declare(strict_types=1);

namespace PluginTests\Tests\Factories\Milhq;

use Forumify\Milhq\Entity\MissionRsvp;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<MissionRsvp>
 */
class MissionRsvpFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return MissionRsvp::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'mission' => MissionFactory::new(),
            'soldier' => SoldierFactory::new(),
            'going' => true,
        ];
    }
}
