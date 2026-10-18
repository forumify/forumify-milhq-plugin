<?php

declare(strict_types=1);

namespace Forumify\Milhq\Components;

use Doctrine\ORM\QueryBuilder;
use Forumify\Milhq\Entity\Record\CombatRecord;
use Forumify\Milhq\Entity\Record\RecordInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

#[AsLiveComponent('Milhq\\CombatRecordTable', '@ForumifyMilhqPlugin/frontend/components/record_table.html.twig')]
class CombatRecordTable extends AbstractRecordTable
{
    protected function getEntityClass(): string
    {
        return CombatRecord::class;
    }

    /**
     * @param CombatRecord $record
     */
    protected function getActionsColumnContext(RecordInterface $record): array
    {
        return ['mission' => $record->getAfterActionReport()?->getMission()];
    }

    protected function getQuery(array $search): QueryBuilder
    {
        return parent::getQuery($search)
            ->leftJoin('e.afterActionReport', 'aar')
            ->leftJoin('aar.mission', 'mission')
            ->leftJoin('mission.operation', 'operation')
            ->addSelect('aar', 'mission', 'operation');
    }
}
