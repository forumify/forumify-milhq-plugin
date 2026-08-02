<?php

declare(strict_types=1);

namespace Forumify\Milhq\Repository;

use Exception;
use Forumify\Core\Repository\AbstractRepository;
use Forumify\Milhq\Entity\GroupedEntityInterface;

/**
 * Only for repositories whose entity implements GroupedEntityInterface.
 *
 * @phpstan-require-extends AbstractRepository
 */
trait GroupScopedRepositoryTrait
{
    /**
     * @param GroupedEntityInterface $entity
     */
    public function getHighestPosition(object $entity): int
    {
        $qb = $this
            ->createQueryBuilder('e')
            ->select('MAX(e.position)');

        GroupScope::apply($qb, $entity->getGroup());

        try {
            return (int)$qb->getQuery()->getSingleScalarResult();
        } catch (Exception) {
            return 0;
        }
    }
}
