<?php

declare(strict_types=1);

namespace Forumify\Milhq\Admin\Controller\Records;

use Forumify\Milhq\Entity\Record\AwardRecord;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/records/award', 'award_records')]
#[IsGranted('milhq.admin.records.award_records.view')]
class AwardRecordController extends AbstractRecordCrudController
{
    protected ?string $permissionView = 'milhq.admin.records.award_records.view';
    protected ?string $permissionCreate = 'milhq.admin.records.award_records.create';
    protected ?string $permissionDelete = 'milhq.admin.records.award_records.delete';

    protected function getRecordType(): string
    {
        return 'award';
    }

    protected function getEntityClass(): string
    {
        return AwardRecord::class;
    }

    protected function getTableName(): string
    {
        return 'Milhq\\AdminAwardRecordTable';
    }
}
