<?php

declare(strict_types=1);

namespace Forumify\Milhq\Admin\Component;

use DateTime;
use Forumify\Core\Component\Table\AbstractDoctrineTable;
use Forumify\Milhq\Entity\Operation;
use Forumify\Plugin\Attribute\PluginVersion;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

#[PluginVersion('forumify/forumify-milhq-plugin', 'premium')]
#[AsLiveComponent('Milhq\\OperationTable', '@Forumify/components/table/table.html.twig')]
#[IsGranted('forumify-milhq.admin.operations.view')]
class OperationTable extends AbstractDoctrineTable
{
    public function __construct()
    {
        $this->sort = ['start' => 'DESC'];
    }

    protected function getEntityClass(): string
    {
        return Operation::class;
    }

    protected function buildTable(): void
    {
        $this
            ->addColumn('title', [
                'field' => 'title',
            ])
            ->addColumn('start', [
                'field' => 'start',
                'renderer' => fn (?DateTime $start) => $start?->format('Y-m-d'),
            ])
            ->addColumn('end', [
                'field' => 'end',
                'renderer' => fn (?DateTime $start) => $start?->format('Y-m-d'),
            ])
            ->addColumn('actions', [
                'field' => 'id',
                'label' => '',
                'renderer' => $this->renderActions(...),
                'searchable' => false,
                'sortable' => false,
            ]);
    }

    private function renderActions(int $id, Operation $operation): string
    {
        $actions = '';
        if ($this->security->isGranted('forumify-milhq.admin.operations.manage')) {
            $actions .= $this->renderAction('milhq_admin_operations_edit', ['identifier' => $id], 'pencil-simple-line');
            $actions .= $this->renderAction('forumify_admin_acl', (array)$operation->getACLParameters(), 'lock-simple');
        }

        if ($this->security->isGranted('forumify-milhq.admin.operations.delete')) {
            $actions .= $this->renderAction('milhq_admin_operations_delete', ['identifier' => $id], 'x');
        }

        return $actions;
    }
}
