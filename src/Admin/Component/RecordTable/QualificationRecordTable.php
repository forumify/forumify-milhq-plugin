<?php

declare(strict_types=1);

namespace Forumify\Milhq\Admin\Component\RecordTable;

use Forumify\Milhq\Entity\Record\QualificationRecord;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

#[AsLiveComponent('Milhq\\AdminQualificationRecordTable', '@Forumify/components/table/table.html.twig')]
#[IsGranted('forumify-milhq.admin.records.qualification_records.view')]
class QualificationRecordTable extends AbstractAdminRecordTable
{
    protected function getEntityClass(): string
    {
        return QualificationRecord::class;
    }

    protected function addRecordColumns(): static
    {
        $this->addColumn('qualification', [
            'field' => 'qualification.name',
        ]);

        return $this;
    }

    protected function getRecordType(): string
    {
        return 'qualification_records';
    }
}
