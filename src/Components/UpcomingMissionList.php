<?php

declare(strict_types=1);

namespace Forumify\Milhq\Components;

use DateTime;
use Doctrine\ORM\QueryBuilder;
use Forumify\Core\Component\List\AbstractDoctrineList;
use Forumify\Milhq\Entity\Mission;
use Forumify\Milhq\Repository\OperationRepository;
use Forumify\Plugin\Attribute\PluginVersion;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

/**
 * @extends AbstractDoctrineList<Mission>
 */
#[PluginVersion('forumify/forumify-milhq-plugin', 'premium')]
#[AsLiveComponent('Milhq\\UpcomingMissionList', '@ForumifyMilhqPlugin/frontend/components/upcoming_mission_list.html.twig')]
class UpcomingMissionList extends AbstractDoctrineList
{
    #[LiveProp]
    public int $size = 5;

    public function __construct(private readonly OperationRepository $operationRepository)
    {
    }

    protected function getEntityClass(): string
    {
        return Mission::class;
    }

    protected function getQuery(): QueryBuilder
    {
        $qb = parent::getQuery()
            ->innerJoin('e.operation', 'o')
            ->where('e.start > :start')
            ->setParameter('start', new DateTime())
            ->orderBy('e.start', 'ASC');

        $this->operationRepository->addACLToQuery($qb, 'view_missions', alias: 'o');
        return $qb;
    }
}
