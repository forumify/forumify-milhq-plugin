<?php

declare(strict_types=1);

namespace Forumify\Milhq\Repository;

use Doctrine\ORM\QueryBuilder;
use Forumify\Core\Repository\AbstractRepository;
use Forumify\Core\Security\VoterAttribute;
use Forumify\Milhq\Entity\Award;
use Forumify\Milhq\Entity\AwardGroup;
use Symfony\Contracts\Service\ResetInterface;

/**
 * @extends AbstractRepository<Award>
 */
class AwardRepository extends AbstractRepository implements GroupScopedRepositoryInterface, ResetInterface
{
    use GroupScopedRepositoryTrait;

    /** @var array<string, array{awards: array<mixed>, groups: array<mixed>}> */
    private array $viewableIds = [];

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

    public function addViewACLToQuery(QueryBuilder $qb, string $alias = 'e'): QueryBuilder
    {
        if ($this->security->isGranted(VoterAttribute::SuperAdmin->value)) {
            return $qb;
        }

        ['awards' => $awardIds, 'groups' => $groupIds] = $this->getViewableIds();
        if (empty($awardIds)) {
            return $qb->andWhere('1 = 0');
        }

        $qb->andWhere("$alias.id IN (:viewableAwardIds)")->setParameter('viewableAwardIds', $awardIds);

        return empty($groupIds)
            ? $qb->andWhere("$alias.group IS NULL")
            : $qb
                ->andWhere("($alias.group IS NULL OR $alias.group IN (:viewableAwardGroupIds))")
                ->setParameter('viewableAwardGroupIds', $groupIds);
    }

    public function reset(): void
    {
        $this->viewableIds = [];
    }

    /**
     * @return array{awards: array<mixed>, groups: array<mixed>}
     */
    private function getViewableIds(): array
    {
        $key = $this->security->getUser()?->getUserIdentifier() ?? '';

        return $this->viewableIds[$key] ??= [
            'awards' => $this
                ->addACLToQuery($this->createQueryBuilder('a')->select('a.id'), 'view', alias: 'a')
                ->getQuery()
                ->getSingleColumnResult(),
            'groups' => $this
                ->addACLToQuery(
                    $this->getEntityManager()->createQueryBuilder()->select('g.id')->from(AwardGroup::class, 'g'),
                    'view',
                    AwardGroup::class,
                    'g',
                )
                ->getQuery()
                ->getSingleColumnResult(),
        ];
    }
}
