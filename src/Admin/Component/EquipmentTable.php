<?php

declare(strict_types=1);

namespace Forumify\Milhq\Admin\Component;

use Forumify\Core\Component\Table\AbstractDoctrineTable;
use Forumify\Milhq\Entity\Enum\EquipmentType;
use Forumify\Milhq\Entity\Equipment;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

#[AsLiveComponent('Milhq\\EquipmentTable', '@Forumify/components/table/table.html.twig')]
#[IsGranted('milhq.admin.organization.equipment.view')]
class EquipmentTable extends AbstractDoctrineTable
{
    protected ?string $permissionReorder = 'milhq.admin.organization.equipment.manage';

    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    protected function getEntityClass(): string
    {
        return Equipment::class;
    }

    protected function buildTable(): void
    {
        $this
            ->addColumn('name', [
                'field' => 'name',
                'sortable' => true,
            ])
            ->addColumn('type', [
                'field' => 'type',
                'sortable' => true,
                'renderer' => fn(EquipmentType $type) => $this->translator->trans('milhq.equipment.type.' . $type->value),
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
        if ($this->security->isGranted('milhq.admin.organization.equipment.manage')) {
            $actions .= $this->renderAction('milhq_admin_equipment_edit', ['identifier' => $id], 'pencil-simple-line');
        }

        if ($this->security->isGranted('milhq.admin.organization.equipment.delete')) {
            $actions .= $this->renderAction('milhq_admin_equipment_delete', ['identifier' => $id], 'x');
        }

        return $actions;
    }
}
