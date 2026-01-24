<?php

declare(strict_types=1);

namespace Forumify\Milhq\Admin\Controller\Records;

use Forumify\Milhq\Entity\Record\RankRecord;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/records/rank', 'rank_records')]
#[IsGranted('forumify-milhq.admin.records.rank_records.view')]
class RankRecordController extends AbstractRecordCrudController
{
    protected ?string $permissionView = 'forumify-milhq.admin.records.rank_records.view';
    protected ?string $permissionCreate = 'forumify-milhq.admin.records.rank_records.create';
    protected ?string $permissionDelete = 'forumify-milhq.admin.records.rank_records.delete';

    protected function getRecordType(): string
    {
        return 'rank';
    }

    protected function getEntityClass(): string
    {
        return RankRecord::class;
    }

    protected function getTableName(): string
    {
        return 'Milhq\\AdminRankRecordTable';
    }
}
