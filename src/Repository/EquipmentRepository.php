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

    public function findByType(EquipmentType $type): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.type = :type')
            ->setParameter('type', $type)
            ->getQuery()
            ->getResult();
    }
}
