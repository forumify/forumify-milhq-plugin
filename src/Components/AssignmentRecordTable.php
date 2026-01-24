<?php

declare(strict_types=1);

namespace Forumify\Milhq\Components;

use Doctrine\ORM\QueryBuilder;
use Forumify\Milhq\Entity\Record\AssignmentRecord;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

#[AsLiveComponent('Milhq\\AssignmentRecordTable', '@ForumifyMilhqPlugin/frontend/components/record_table.html.twig')]
class AssignmentRecordTable extends AbstractRecordTable
{
    protected array $searchFields = ['position.name', 'unit.name'];

    protected function getEntityClass(): string
    {
        return AssignmentRecord::class;
    }

    protected function buildTable(): void
    {
        $this
            ->addDateColumn()
            ->addColumn('unit', [
                'class' => 'text-left text-small',
                'field' => 'unit.name',
                'searchable' => false,
                'sortable' => false,
            ])
            ->addColumn('position', [
                'class' => 'text-left text-small',
                'field' => 'position.name',
                'searchable' => false,
                'sortable' => false,
            ]);
    }

    protected function getQuery(array $search): QueryBuilder
    {
        $qb = parent::getQuery($search);
        return $qb->andWhere($qb->expr()->orX('e.position IS NOT NULL', 'e.unit IS NOT NULL'));
    }
}
