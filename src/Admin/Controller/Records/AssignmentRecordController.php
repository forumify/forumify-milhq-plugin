<?php

declare(strict_types=1);

namespace Forumify\Milhq\Admin\Controller\Records;

use Forumify\Milhq\Entity\Record\AssignmentRecord;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/records/assignment', 'assignment_records')]
#[IsGranted('milhq.admin.records.assignment_records.view')]
class AssignmentRecordController extends AbstractRecordCrudController
{
    protected ?string $permissionView = 'milhq.admin.records.assignment_records.view';
    protected ?string $permissionCreate = 'milhq.admin.records.assignment_records.create';
    protected ?string $permissionDelete = 'milhq.admin.records.assignment_records.delete';

    protected function getRecordType(): string
    {
        return 'assignment';
    }

    protected function getEntityClass(): string
    {
        return AssignmentRecord::class;
    }

    protected function getTableName(): string
    {
        return 'Milhq\\AdminAssignmentRecordTable';
    }
}
