<?php

declare(strict_types=1);

namespace Forumify\Milhq\Repository;

use Doctrine\ORM\QueryBuilder;
use Forumify\Core\Repository\AbstractRepository;
use Forumify\Core\Security\VoterAttribute;
use Forumify\Milhq\Entity\Qualification;
use Forumify\Milhq\Entity\QualificationGroup;
use Symfony\Contracts\Service\ResetInterface;

/**
 * @extends AbstractRepository<Qualification>
 */
class QualificationRepository extends AbstractRepository implements GroupScopedRepositoryInterface, ResetInterface
{
    use GroupScopedRepositoryTrait;

    /** @var array<string, array{qualifications: array<mixed>, groups: array<mixed>}> */
    private array $viewableIds = [];

    public static function getEntityClass(): string
    {
        return Qualification::class;
    }

    /**
     * @return array<Qualification>
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

        ['qualifications' => $qualificationIds, 'groups' => $groupIds] = $this->getViewableIds();
        if (empty($qualificationIds)) {
            return $qb->andWhere('1 = 0');
        }

        $qb->andWhere("$alias.id IN (:viewableQualificationIds)")->setParameter('viewableQualificationIds', $qualificationIds);

        return empty($groupIds)
            ? $qb->andWhere("$alias.group IS NULL")
            : $qb
                ->andWhere("($alias.group IS NULL OR $alias.group IN (:viewableQualificationGroupIds))")
                ->setParameter('viewableQualificationGroupIds', $groupIds);
    }

    public function reset(): void
    {
        $this->viewableIds = [];
    }

    /**
     * @return array{qualifications: array<mixed>, groups: array<mixed>}
     */
    private function getViewableIds(): array
    {
        $key = $this->security->getUser()?->getUserIdentifier() ?? '';

        return $this->viewableIds[$key] ??= [
            'qualifications' => $this
                ->addACLToQuery($this->createQueryBuilder('q')->select('q.id'), 'view', alias: 'q')
                ->getQuery()
                ->getSingleColumnResult(),
            'groups' => $this
                ->addACLToQuery(
                    $this->getEntityManager()->createQueryBuilder()->select('g.id')->from(QualificationGroup::class, 'g'),
                    'view',
                    QualificationGroup::class,
                    'g',
                )
                ->getQuery()
                ->getSingleColumnResult(),
        ];
    }
}
