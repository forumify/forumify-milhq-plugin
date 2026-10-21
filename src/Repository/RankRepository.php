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

    /** @var array<string, array{ranks: array<mixed>, groups: array<mixed>}> */
    private array $viewableIds = [];

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

        ['ranks' => $rankIds, 'groups' => $groupIds] = $this->getViewableIds();
        if (empty($rankIds)) {
            return $qb->andWhere('1 = 0');
        }

        $qb->andWhere("$alias.id IN (:viewableRankIds)")->setParameter('viewableRankIds', $rankIds);

        return empty($groupIds)
            ? $qb->andWhere("$alias.group IS NULL")
            : $qb
                ->andWhere("($alias.group IS NULL OR $alias.group IN (:viewableRankGroupIds))")
                ->setParameter('viewableRankGroupIds', $groupIds);
    }

    public function reset(): void
    {
        $this->viewableIds = [];
    }

    /**
     * @return array{ranks: array<mixed>, groups: array<mixed>}
     */
    private function getViewableIds(): array
    {
        $key = $this->security->getUser()?->getUserIdentifier() ?? '';

        return $this->viewableIds[$key] ??= [
            'ranks' => $this
                ->addACLToQuery($this->createQueryBuilder('r')->select('r.id'), 'view', alias: 'r')
                ->getQuery()
                ->getSingleColumnResult(),
            'groups' => $this
                ->addACLToQuery(
                    $this->getEntityManager()->createQueryBuilder()->select('g.id')->from(RankGroup::class, 'g'),
                    'view',
                    RankGroup::class,
                    'g',
                )
                ->getQuery()
                ->getSingleColumnResult(),
        ];
    }
}
