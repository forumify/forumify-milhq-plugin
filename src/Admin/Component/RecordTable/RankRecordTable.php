<?php

declare(strict_types=1);

namespace Forumify\Milhq\Admin\Component\RecordTable;

use Forumify\Milhq\Entity\Record\RankRecord;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

#[AsLiveComponent('Milhq\\AdminRankRecordTable', '@Forumify/components/table/table.html.twig')]
#[IsGranted('milhq.admin.records.rank_records.view')]
class RankRecordTable extends AbstractAdminRecordTable
{
    protected function getEntityClass(): string
    {
        return RankRecord::class;
    }

    protected function addRecordColumns(): static
    {
        $this
            ->addColumn('type', [
                'field' => 'type',
            ])
            ->addColumn('rank', [
                'field' => 'rank.name',
            ])
        ;

        return $this;
    }

    protected function getRecordType(): string
    {
        return 'rank_records';
    }
}
