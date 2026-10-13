<?php

declare(strict_types=1);

namespace Forumify\Milhq\Components;

use Doctrine\ORM\QueryBuilder;
use Forumify\Core\Component\List\AbstractDoctrineList;
use Forumify\Milhq\Entity\Qualification;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

/**
 * @extends AbstractDoctrineList<Qualification>
 */
#[AsLiveComponent('Milhq\\QualificationList', '@ForumifyMilhqPlugin/frontend/components/qualification_list.html.twig')]
class QualificationList extends AbstractDoctrineList
{
    #[LiveProp]
    public ?int $groupId = null;

    protected function getEntityClass(): string
    {
        return Qualification::class;
    }

    protected function getQuery(): QueryBuilder
    {
        $qb = parent::getQuery();

        return $this->groupId === null
            ? $qb->andWhere('e.group IS NULL')
            : $qb->andWhere('e.group = :group')->setParameter('group', $this->groupId);
    }
}
