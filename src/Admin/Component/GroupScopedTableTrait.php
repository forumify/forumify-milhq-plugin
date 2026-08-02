<?php

declare(strict_types=1);

namespace Forumify\Milhq\Admin\Component;

use Doctrine\ORM\QueryBuilder;
use Forumify\Core\Component\Table\AbstractDoctrineTable;
use Forumify\Core\Entity\SortableEntityInterface;
use Forumify\Milhq\Entity\GroupedEntityInterface;
use Forumify\Milhq\Repository\GroupScope;

/**
 * @phpstan-require-extends AbstractDoctrineTable
 */
trait GroupScopedTableTrait
{
    protected function reorderItem(SortableEntityInterface $entity, string $direction): void
    {
        if (!$entity instanceof GroupedEntityInterface) {
            parent::reorderItem($entity, $direction);
            return;
        }

        $this->repository->reorder(
            $entity,
            $direction,
            static fn (QueryBuilder $qb) => GroupScope::apply($qb, $entity->getGroup()),
        );
    }
}
