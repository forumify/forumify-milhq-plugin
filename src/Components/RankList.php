<?php

declare(strict_types=1);

namespace Forumify\Milhq\Components;

use Doctrine\ORM\QueryBuilder;
use Forumify\Core\Component\List\AbstractDoctrineList;
use Forumify\Milhq\Entity\Rank;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

/**
 * @extends AbstractDoctrineList<Rank>
 */
#[AsLiveComponent('RankList', '@ForumifyMilhqPlugin/frontend/components/rank_list.html.twig')]
class RankList extends AbstractDoctrineList
{
    #[LiveProp]
    public ?int $groupId = null;

    protected function getEntityClass(): string
    {
        return Rank::class;
    }

    protected function getQuery(): QueryBuilder
    {
        $qb = parent::getQuery();

        return $this->groupId === null
            ? $qb->andWhere('e.group IS NULL')
            : $qb->andWhere('e.group = :group')->setParameter('group', $this->groupId);
    }
}
