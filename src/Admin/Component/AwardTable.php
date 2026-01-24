<?php

declare(strict_types=1);

namespace Forumify\Milhq\Admin\Component;

use Forumify\Core\Component\Table\AbstractDoctrineTable;
use Forumify\Milhq\Entity\Award;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

#[AsLiveComponent('Milhq\\AwardTable', '@Forumify/components/table/table.html.twig')]
#[IsGranted('forumify-milhq.admin.organization.awards.view')]
class AwardTable extends AbstractDoctrineTable
{
    protected ?string $permissionReorder = 'forumify-milhq.admin.organization.awards.manage';

    protected function getEntityClass(): string
    {
        return Award::class;
    }

    protected function buildTable(): void
    {
        $this
            ->addPositionColumn()
            ->addColumn('name', [
                'field' => 'name',
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

        if ($this->security->isGranted('forumify-milhq.admin.organization.awards.manage')) {
            $actions .= $this->renderAction('milhq_admin_award_edit', ['identifier' => $id], 'pencil-simple-line');
        }

        if ($this->security->isGranted('forumify-milhq.admin.organization.awards.delete')) {
            $actions .= $this->renderAction('milhq_admin_award_delete', ['identifier' => $id], 'x');
        }

        return $actions;
    }
}
