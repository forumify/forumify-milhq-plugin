<?php

declare(strict_types=1);

namespace Forumify\Milhq\Repository;

use Forumify\Core\Repository\AbstractRepository;
use Forumify\Milhq\Entity\Award;

/**
 * @extends AbstractRepository<Award>
 */
class AwardRepository extends AbstractRepository implements GroupScopedRepositoryInterface
{
    use GroupScopedRepositoryTrait;

    public static function getEntityClass(): string
    {
        return Award::class;
    }

    /**
     * @return array<Award>
     */
    public function findByNameLike(string $name): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.name LIKE :name')
            ->setParameter('name', '%' . $name . '%')
            ->orderBy('e.position', 'ASC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();
    }
}
