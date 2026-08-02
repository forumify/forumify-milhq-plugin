<?php

declare(strict_types=1);

namespace Forumify\Milhq\Repository;

use Exception;
use Forumify\Core\Repository\AbstractRepository;
use Forumify\Milhq\Entity\GroupedEntityInterface;

/**
 * @phpstan-require-extends AbstractRepository
 */
trait GroupScopedRepositoryTrait
{
    public function getHighestPosition(object $entity): int
    {
        if (!$entity instanceof GroupedEntityInterface) {
            return parent::getHighestPosition($entity);
        }

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
