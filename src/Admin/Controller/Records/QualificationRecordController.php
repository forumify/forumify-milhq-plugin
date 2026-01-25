<?php

declare(strict_types=1);

namespace Forumify\Milhq\Admin\Controller\Records;

use Forumify\Milhq\Entity\Record\QualificationRecord;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/records/qualification', 'qualification_records')]
#[IsGranted('milhq.admin.records.qualification_records.view')]
class QualificationRecordController extends AbstractRecordCrudController
{
    protected ?string $permissionView = 'milhq.admin.records.qualification_records.view';
    protected ?string $permissionCreate = 'milhq.admin.records.qualification_records.create';
    protected ?string $permissionDelete = 'milhq.admin.records.qualification_records.delete';

    protected function getRecordType(): string
    {
        return 'qualification';
    }

    protected function getEntityClass(): string
    {
        return QualificationRecord::class;
    }

    protected function getTableName(): string
    {
        return 'Milhq\\AdminQualificationRecordTable';
    }
}
