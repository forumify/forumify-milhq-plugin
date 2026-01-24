<?php

declare(strict_types=1);

namespace Forumify\Milhq\Components;

use Forumify\Core\Component\List\AbstractDoctrineList;
use Forumify\Milhq\Entity\Rank;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * @extends AbstractDoctrineList<Rank>
 */
#[AsLiveComponent('RankList', '@ForumifyMilhqPlugin/frontend/components/rank_list.html.twig')]
class RankList extends AbstractDoctrineList
{
    protected function getEntityClass(): string
    {
        return Rank::class;
    }
}
