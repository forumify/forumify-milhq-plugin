<?php

namespace Forumify\Milhq\Entity\Enum;

enum EquipmentType: string
{
    case PrimaryWeapon = 'primary_weapon';
    case SecondaryWeapon = 'secondary_weapon';
    case Vehicle = 'vehicle';

    public function getLabel(): string
    {
        return match($this) {
            self::PrimaryWeapon => 'Primary Weapon',
            self::SecondaryWeapon => 'Secondary Weapon',
            self::Vehicle => 'Vehicle',
        };
    }
}
