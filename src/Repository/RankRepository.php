<?php

declare(strict_types=1);

namespace Forumify\Milhq\Repository;

use Doctrine\ORM\QueryBuilder;
use Forumify\Core\Repository\AbstractRepository;
use Forumify\Core\Security\VoterAttribute;
use Forumify\Milhq\Entity\Rank;
use Forumify\Milhq\Entity\RankGroup;
use Symfony\Contracts\Service\ResetInterface;

/**
 * @extends AbstractRepository<Rank>
 */
class RankRepository extends AbstractRepository implements GroupScopedRepositoryInterface, ResetInterface
{
    use GroupScopedRepositoryTrait;

    /** @var array<string, array<mixed>> */
    private array $viewableGroupIds = [];

    public static function getEntityClass(): string
    {
        return Rank::class;
    }

    /**
     * @return array<Rank>
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

    public function addViewACLToQuery(QueryBuilder $qb, string $alias = 'e'): QueryBuilder
    {
        if ($this->security->isGranted(VoterAttribute::SuperAdmin->value)) {
            return $qb;
        }

        $groupIds = $this->getViewableGroupIds();

        return empty($groupIds)
            ? $qb->andWhere("$alias.group IS NULL")
            : $qb
                ->andWhere("($alias.group IS NULL OR $alias.group IN (:viewableRankGroupIds))")
                ->setParameter('viewableRankGroupIds', $groupIds);
    }

    public function reset(): void
    {
        $this->viewableGroupIds = [];
    }

    /**
     * @return array<mixed>
     */
    private function getViewableGroupIds(): array
    {
        $key = $this->security->getUser()?->getUserIdentifier() ?? '';

        return $this->viewableGroupIds[$key] ??= $this
            ->addACLToQuery(
                $this->getEntityManager()->createQueryBuilder()->select('g.id')->from(RankGroup::class, 'g'),
                'view',
                RankGroup::class,
                'g',
            )
            ->getQuery()
            ->getSingleColumnResult();
    }
}
