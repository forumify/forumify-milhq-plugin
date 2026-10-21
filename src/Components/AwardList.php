<?php

declare(strict_types=1);

namespace Forumify\Milhq\Components;

use Doctrine\ORM\QueryBuilder;
use Forumify\Core\Component\List\AbstractDoctrineList;
use Forumify\Milhq\Entity\Award;
use Forumify\Milhq\Repository\AwardRepository;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

/**
 * @extends AbstractDoctrineList<Award>
 */
#[AsLiveComponent('AwardList', '@ForumifyMilhqPlugin/frontend/components/award_list.html.twig')]
class AwardList extends AbstractDoctrineList
{
    #[LiveProp]
    public ?int $groupId = null;

    public function __construct(private readonly AwardRepository $awardRepository)
    {
    }

    protected function getEntityClass(): string
    {
        return Award::class;
    }

    protected function getQuery(): QueryBuilder
    {
        $qb = $this->awardRepository->addViewACLToQuery(parent::getQuery());

        return $this->groupId === null
            ? $qb->andWhere('e.group IS NULL')
            : $qb->andWhere('e.group = :group')->setParameter('group', $this->groupId);
    }
}
