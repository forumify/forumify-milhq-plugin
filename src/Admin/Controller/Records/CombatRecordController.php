<?php

declare(strict_types=1);

namespace Forumify\Milhq\Admin\Controller\Records;

use Forumify\Milhq\Entity\Record\CombatRecord;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/records/combat', 'combat_records')]
#[IsGranted('forumify-milhq.admin.records.combat_records.view')]
class CombatRecordController extends AbstractRecordCrudController
{
    protected ?string $permissionView = 'forumify-milhq.admin.records.combat_records.view';
    protected ?string $permissionCreate = 'forumify-milhq.admin.records.combat_records.create';
    protected ?string $permissionDelete = 'forumify-milhq.admin.records.combat_records.delete';

    protected function getRecordType(): string
    {
        return 'combat';
    }

    protected function getEntityClass(): string
    {
        return CombatRecord::class;
    }

    protected function getTableName(): string
    {
        return 'Milhq\\AdminCombatRecordTable';
    }
}
