<?php

declare(strict_types=1);

namespace Forumify\Milhq\Entity\Enum;

enum EquipmentType: string
{
    case PrimaryWeapon = 'primary_weapon';
    case SecondaryWeapon = 'secondary_weapon';
    case Vehicle = 'vehicle';
}
