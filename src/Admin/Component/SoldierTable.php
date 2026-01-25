<?php

declare(strict_types=1);

namespace Forumify\Milhq\Admin\Component;

use Forumify\Core\Component\Table\AbstractDoctrineTable;
use Forumify\Core\Component\Table\AbstractTable;
use Forumify\Milhq\Entity\Soldier;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Twig\Environment;

#[AsLiveComponent('Milhq\\SoldierTable', '@Forumify/components/table/table.html.twig')]
#[IsGranted('milhq.admin.soldiers.view')]
class SoldierTable extends AbstractDoctrineTable
{
    public function __construct(private readonly Environment $twig)
    {
        $this->sort = ['name' => AbstractTable::SORT_ASC];
    }

    protected function getEntityClass(): string
    {
        return Soldier::class;
    }

    protected function buildTable(): void
    {
        $this
            ->addColumn('name', [
                'field' => 'name',
            ])
            ->addColumn('rank', [
                'field' => 'rank?.name',
                'label' => 'Rank',
            ])
            ->addColumn('position', [
                'field' => 'position?.name',
                'label' => 'Position',
            ])
            ->addColumn('unit', [
                'field' => 'unit?.name',
                'label' => 'Unit',
            ])
            ->addColumn('status', [
                'field' => 'status?.name',
                'label' => 'Status',
                'renderer' => fn ($_, Soldier $user) => $user->getStatus() !== null
                    ? $this->twig->render('@ForumifyMilhqPlugin/frontend/roster/components/status.html.twig', ['status' => $user->getStatus()])
                    : '',
            ])
            ->addColumn('actions', [
                'label' => '',
                'renderer' => fn ($_, $row) => $this->twig->render('@ForumifyMilhqPlugin/admin/soldiers/list/actions.html.twig', ['row' => $row]),
                'searchable' => false,
                'sortable' => false,
            ]);
    }
}
