<?php

declare(strict_types=1);

namespace Forumify\Milhq\Admin\Component\RecordTable;

use Forumify\Milhq\Entity\Record\CombatRecord;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

#[AsLiveComponent('Milhq\\AdminCombatRecordTable', '@Forumify/components/table/table.html.twig')]
#[IsGranted('milhq.admin.records.combat_records.view')]
class CombatRecordTable extends AbstractAdminRecordTable
{
    protected function getEntityClass(): string
    {
        return CombatRecord::class;
    }

    protected function addRecordColumns(): static
    {
        $this->addColumn('text', [
            'field' => 'text',
        ]);

        return $this;
    }

    protected function getRecordType(): string
    {
        return 'combat_records';
    }
}
