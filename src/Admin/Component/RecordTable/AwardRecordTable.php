<?php

declare(strict_types=1);

namespace Forumify\Milhq\Admin\Component\RecordTable;

use Forumify\Milhq\Entity\Record\AwardRecord;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

#[AsLiveComponent('Milhq\\AdminAwardRecordTable', '@Forumify/components/table/table.html.twig')]
#[IsGranted('milhq.admin.records.award_records.view')]
class AwardRecordTable extends AbstractAdminRecordTable
{
    protected function getEntityClass(): string
    {
        return AwardRecord::class;
    }

    protected function addRecordColumns(): static
    {
        $this->addColumn('award', [
            'field' => 'award.name',
        ]);

        return $this;
    }

    protected function getRecordType(): string
    {
        return 'award_records';
    }
}
