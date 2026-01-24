<?php

declare(strict_types=1);

namespace Forumify\Milhq\Admin\Component;

use Forumify\Core\Component\Table\AbstractDoctrineTable;
use Forumify\Milhq\Entity\Form;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

#[AsLiveComponent('Milhq\\FormTable', '@Forumify/components/table/table.html.twig')]
#[IsGranted('forumify-milhq.admin.organization.forms.view')]
class FormTable extends AbstractDoctrineTable
{
    protected function getEntityClass(): string
    {
        return Form::class;
    }

    protected function buildTable(): void
    {
        $this
            ->addColumn('name', [
                'field' => 'name',
            ])
            ->addColumn('submissions', [
                'field' => 'id',
                'renderer' => fn($_, Form $form) => $form->getSubmissions()->count(),
            ])
            ->addColumn('actions', [
                'field' => 'id',
                'label' => '',
                'renderer' => $this->renderActions(...),
                'searchable' => false,
                'sortable' => false,
            ])
        ;
    }

    private function renderActions(int $id, Form $form): string
    {
        $actions = '';

        if ($this->security->isGranted('forumify-milhq.admin.organization.forms.manage')) {
            $actions .= $this->renderAction('milhq_admin_form_edit', ['identifier' => $id], 'pencil-simple-line');
            $actions .= $this->renderAction('forumify_admin_acl', (array)$form->getACLParameters(), 'lock-simple');
            $actions .= $this->renderAction('milhq_admin_form_field_list', ['formId' => $id], 'textbox');
        }

        if ($this->security->isGranted('forumify-milhq.admin.organization.forms.delete')) {
            $actions .= $this->renderAction('milhq_admin_form_delete', ['identifier' => $id], 'x');
        }

        return $actions;
    }
}
