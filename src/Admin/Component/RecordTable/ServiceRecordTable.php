<?php

declare(strict_types=1);

namespace Forumify\Milhq\Admin\Component\RecordTable;

use Forumify\Milhq\Entity\Record\ServiceRecord;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

#[AsLiveComponent('Milhq\\AdminServiceRecordTable', '@Forumify/components/table/table.html.twig')]
#[IsGranted('forumify-milhq.admin.records.service_records.view')]
class ServiceRecordTable extends AbstractAdminRecordTable
{
    protected function getEntityClass(): string
    {
        return ServiceRecord::class;
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
        return 'service_records';
    }
}
