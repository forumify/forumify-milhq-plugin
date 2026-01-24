<?php

declare(strict_types=1);

namespace Forumify\Milhq\Components;

use Forumify\Core\Component\List\AbstractDoctrineList;
use Forumify\Milhq\Entity\Award;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * @extends AbstractDoctrineList<Award>
 */
#[AsLiveComponent('AwardList', '@ForumifyMilhqPlugin/frontend/components/award_list.html.twig')]
class AwardList extends AbstractDoctrineList
{
    protected function getEntityClass(): string
    {
        return Award::class;
    }
}
