<?php

declare(strict_types=1);

namespace Forumify\Milhq\Components;

use DateTime;
use Doctrine\ORM\QueryBuilder;
use Forumify\Core\Component\List\AbstractDoctrineList;
use Forumify\Milhq\Entity\Operation;
use Forumify\Plugin\Attribute\PluginVersion;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

/**
 * @extends AbstractDoctrineList<Operation>
 */
#[PluginVersion('forumify/forumify-milhq-plugin', 'premium')]
#[AsLiveComponent('Milhq\\OperationList', '@ForumifyMilhqPlugin/frontend/components/operation_list.html.twig')]
class OperationList extends AbstractDoctrineList
{
    #[LiveProp]
    public bool $inOpCenter = false;

    protected string|array|null $aclPermission = 'view';

    protected function getEntityClass(): string
    {
        return Operation::class;
    }

    protected function getQuery(): QueryBuilder
    {
        return parent::getQuery()
            ->orderBy('CASE
                WHEN e.start IS NULL AND e.end IS NULL THEN 0
                WHEN :now > e.start AND e.end IS NULL THEN 1
                WHEN :now BETWEEN e.start AND e.end THEN 2
                ELSE 3
                END
            ', 'ASC')
            ->addOrderBy('e.start', 'DESC')
            ->setParameter('now', (new DateTime())->format('Y-m-d'));
    }
}
