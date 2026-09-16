<?php

declare(strict_types=1);

namespace Forumify\Milhq\Repository;

use Doctrine\ORM\QueryBuilder;
use Exception;
use Forumify\Core\Repository\AbstractRepository;
use Forumify\Milhq\Entity\GroupedEntityInterface;
use Forumify\Milhq\Entity\GroupInterface;

/**
 * @phpstan-require-extends AbstractRepository
 * @phpstan-require-implements GroupScopedRepositoryInterface
 */
trait GroupScopedRepositoryTrait
{
    public function applyGroupScope(QueryBuilder $qb, ?GroupInterface $group, string $alias = 'e'): void
    {
        if ($group === null) {
            $qb->andWhere("$alias.group IS NULL");
            return;
        }

        $qb
            ->andWhere("$alias.group = :group")
            ->setParameter('group', $group);
    }

    /**
     * @param GroupedEntityInterface $entity
     */
    public function getHighestPosition(object $entity): int
    {
        $qb = $this
            ->createQueryBuilder('e')
            ->select('MAX(e.position)');

        $this->applyGroupScope($qb, $entity->getGroup());

        try {
            return (int)$qb->getQuery()->getSingleScalarResult();
        } catch (Exception) {
            return 0;
        }
    }
}
