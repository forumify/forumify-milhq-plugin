<?php

declare(strict_types=1);

namespace Forumify\Milhq\Repository;

use Doctrine\ORM\QueryBuilder;
use Forumify\Milhq\Entity\GroupInterface;

final class GroupScope
{
    private function __construct()
    {
    }

    public static function apply(QueryBuilder $qb, ?GroupInterface $group, string $alias = 'e'): void
    {
        if ($group === null) {
            $qb->andWhere("$alias.group IS NULL");
            return;
        }

        $qb
            ->andWhere("$alias.group = :group")
            ->setParameter('group', $group);
    }
}
