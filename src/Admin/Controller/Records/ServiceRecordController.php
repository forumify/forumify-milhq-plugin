<?php

declare(strict_types=1);

namespace Forumify\Milhq\Admin\Controller\Records;

use Forumify\Milhq\Entity\Record\ServiceRecord;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/records/service', 'service_records')]
#[IsGranted('forumify-milhq.admin.records.service_records.view')]
class ServiceRecordController extends AbstractRecordCrudController
{
    protected ?string $permissionView = 'forumify-milhq.admin.records.service_records.view';
    protected ?string $permissionCreate = 'forumify-milhq.admin.records.service_records.create';
    protected ?string $permissionDelete = 'forumify-milhq.admin.records.service_records.delete';

    protected function getRecordType(): string
    {
        return 'service';
    }

    protected function getEntityClass(): string
    {
        return ServiceRecord::class;
    }

    protected function getTableName(): string
    {
        return 'Milhq\\AdminServiceRecordTable';
    }
}
