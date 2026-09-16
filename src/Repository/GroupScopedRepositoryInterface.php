<?php

declare(strict_types=1);

namespace Forumify\Milhq\Repository;

use Doctrine\ORM\QueryBuilder;
use Forumify\Milhq\Entity\GroupInterface;

interface GroupScopedRepositoryInterface
{
    public function applyGroupScope(QueryBuilder $qb, ?GroupInterface $group, string $alias = 'e'): void;
}
