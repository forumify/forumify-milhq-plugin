<?php

declare(strict_types=1);

namespace Forumify\Milhq\Repository;

use Forumify\Core\Repository\AbstractRepository;
use Forumify\Milhq\Entity\Soldier;
use Forumify\Milhq\Entity\Unit;

/**
 * @extends AbstractRepository<Unit>
 */
class UnitRepository extends AbstractRepository
{
    public static function getEntityClass(): string
    {
        return Unit::class;
    }

    public function findBySoldierIsSupervisor(Soldier|int $soldier): array
    {
        $soldierId = $soldier instanceof Soldier ? $soldier->getId() : $soldier;

        return $this->createQueryBuilder('e')
            ->innerJoin('e.supervisors', 'supervisor')
            ->innerJoin(Soldier::class, 'soldier', 'WITH', 'soldier.position = supervisor AND soldier.unit = e')
            ->where('soldier.id = :soldierId')
            ->setParameter('soldierId', $soldierId)
            ->getQuery()
            ->getResult();
    }
}
