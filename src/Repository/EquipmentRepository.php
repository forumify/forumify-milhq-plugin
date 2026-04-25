<?php

declare(strict_types=1);

namespace Forumify\Milhq\Repository;

use Forumify\Core\Repository\AbstractRepository;
use Forumify\Milhq\Entity\Enum\EquipmentType;
use Forumify\Milhq\Entity\Equipment;

/**
 * @extends AbstractRepository<Equipment>
 */
class EquipmentRepository extends AbstractRepository
{
    public static function getEntityClass(): string
    {
        return Equipment::class;
    }

    /**
     * @return array<Equipment>
     */
    public function findByType(EquipmentType $type): array
    {
        return $this->findBy(['type' => $type->value]);
    }
}
