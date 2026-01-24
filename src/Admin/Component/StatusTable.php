<?php

declare(strict_types=1);

namespace Forumify\Milhq\Admin\Component;

use Forumify\Core\Component\Table\AbstractDoctrineTable;
use Forumify\Milhq\Entity\Status;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Twig\Environment;

#[AsLiveComponent('Milhq\\StatusTable', '@Forumify/components/table/table.html.twig')]
#[IsGranted('forumify-milhq.admin.organization.statuses.view')]
class StatusTable extends AbstractDoctrineTable
{
    protected ?string $permissionReorder = 'forumify-milhq.admin.organization.statuses.manage';

    public function __construct(private readonly Environment $twig)
    {
    }

    protected function getEntityClass(): string
    {
        return Status::class;
    }

    protected function buildTable(): void
    {
        $this
            ->addPositionColumn()
            ->addColumn('name', [
                'field' => 'name',
                'sortable' => true,
            ])
            ->addColumn('appearance', [
                'renderer' => fn ($_, $status) => $this->twig->render('@ForumifyMilhqPlugin/frontend/roster/components/status.html.twig', ['status' => $status]),
                'searchable' => false,
                'sortable' => false,
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
        if ($this->security->isGranted('forumify-milhq.admin.organization.statuses.manage')) {
            $actions .= $this->renderAction('milhq_admin_status_edit', ['identifier' => $id], 'pencil-simple-line');
        }

        if ($this->security->isGranted('forumify-milhq.admin.organization.statuses.delete')) {
            $actions .= $this->renderAction('milhq_admin_status_delete', ['identifier' => $id], 'x');
        }

        return $actions;
    }
}
