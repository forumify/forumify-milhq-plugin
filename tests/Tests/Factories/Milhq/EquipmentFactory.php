<?php

declare(strict_types=1);

namespace PluginTests\Tests\Factories\Milhq;

use Forumify\Milhq\Entity\Enum\EquipmentType;
use Forumify\Milhq\Entity\Equipment;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

class EquipmentFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return Equipment::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'name' => self::faker()->word(),
            'type' => EquipmentType::PrimaryWeapon,
        ];
    }
}
