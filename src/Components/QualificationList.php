<?php

declare(strict_types=1);

namespace Forumify\Milhq\Components;

use Forumify\Core\Component\List\AbstractDoctrineList;
use Forumify\Milhq\Entity\Qualification;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * @extends AbstractDoctrineList<Qualification>
 */
#[AsLiveComponent('Milhq\\QualificationList', '@ForumifyMilhqPlugin/frontend/components/qualification_list.html.twig')]
class QualificationList extends AbstractDoctrineList
{
    protected function getEntityClass(): string
    {
        return Qualification::class;
    }
}
