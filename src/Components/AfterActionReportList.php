<?php

declare(strict_types=1);

namespace Forumify\Milhq\Components;

use Doctrine\ORM\QueryBuilder;
use Forumify\Core\Component\List\AbstractDoctrineList;
use Forumify\Milhq\Entity\AfterActionReport;
use Forumify\Milhq\Entity\Mission;
use Forumify\Plugin\Attribute\PluginVersion;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

/**
 * @extends AbstractDoctrineList<AfterActionReport>
 */
#[PluginVersion('forumify/forumify-milhq-plugin', 'premium')]
#[AsLiveComponent('Milhq\\AfterActionReportList', '@ForumifyMilhqPlugin/frontend/components/aar_list.html.twig')]
class AfterActionReportList extends AbstractDoctrineList
{
    #[LiveProp]
    public Mission $mission;

    protected function getEntityClass(): string
    {
        return AfterActionReport::class;
    }

    protected function getQuery(): QueryBuilder
    {
        return parent::getQuery()
            ->where('e.mission = :mission')
            ->join('e.unit', 'u')
            ->orderBy('u.position')
            ->setParameter('mission', $this->mission);
    }
}
