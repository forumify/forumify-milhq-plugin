<?php

declare(strict_types=1);

namespace Forumify\Milhq\Admin\Component;

use Forumify\Core\Component\Table\AbstractDoctrineTable;
use Forumify\Milhq\Entity\Specialty;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

#[AsLiveComponent('Milhq\\SpecialtyTable', '@Forumify/components/table/table.html.twig')]
#[IsGranted('forumify-milhq.admin.organization.specialties.view')]
class SpecialtyTable extends AbstractDoctrineTable
{
    protected ?string $permissionReorder = 'forumify-milhq.admin.organization.specialties.manage';

    protected function getEntityClass(): string
    {
        return Specialty::class;
    }

    protected function buildTable(): void
    {
        $this
            ->addPositionColumn()
            ->addColumn('name', [
                'field' => 'name',
                'sortable' => true,
            ])
            ->addColumn('abbreviation', [
                'field' => 'abbreviation',
                'sortable' => true,
            ])
            ->addColumn('actions', [
                'field' => 'id',
                'label' => '',
                'renderer' => $this->renderActions(...),
                'searchable' => false,
                'sortable' => false,
            ]);
    }

    private function renderActions(int $id): string
    {
        $actions = '';
        if ($this->security->isGranted('forumify-milhq.admin.organization.specialties.manage')) {
            $actions .= $this->renderAction('milhq_admin_specialty_edit', ['identifier' => $id], 'pencil-simple-line');
        }

        if ($this->security->isGranted('forumify-milhq.admin.organization.specialties.delete')) {
            $actions .= $this->renderAction('milhq_admin_specialty_delete', ['identifier' => $id], 'x');
        }

        return $actions;
    }
}
