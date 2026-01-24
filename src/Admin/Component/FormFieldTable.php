<?php

declare(strict_types=1);

namespace Forumify\Milhq\Admin\Component;

use Doctrine\ORM\QueryBuilder;
use Forumify\Core\Component\Table\AbstractDoctrineTable;
use Forumify\Core\Entity\SortableEntityInterface;
use Forumify\Milhq\Entity\Form;
use Forumify\Milhq\Entity\FormField;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

#[AsLiveComponent('Milhq\\FormFieldTable', '@Forumify/components/table/table.html.twig')]
#[IsGranted('forumify-milhq.admin.organization.forms.manage')]
class FormFieldTable extends AbstractDoctrineTable
{
    #[LiveProp]
    public Form $form;

    protected ?string $permissionReorder = 'forumify-milhq.admin.organization.forms.manage';

    public function __construct()
    {
        $this->sort = ['position' => self::SORT_ASC];
    }

    protected function getEntityClass(): string
    {
        return FormField::class;
    }

    protected function buildTable(): void
    {
        $this
            ->addPositionColumn()
            ->addColumn('label', [
                'field' => 'label',
            ])
            ->addColumn('type', [
                'field' => 'type',
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

    protected function getQuery(array $search): QueryBuilder
    {
        return parent::getQuery($search)
            ->andWhere('e.form = :form')
            ->setParameter('form', $this->form)
        ;
    }

    protected function reorderItem(SortableEntityInterface $entity, string $direction): void
    {
        $this->repository->reorder(
            $entity,
            $direction,
            fn(QueryBuilder $qb) => $qb
                ->andWhere('e.form = :form')
                ->setParameter('form', $this->form),
        );
    }

    private function renderActions(int $id): string
    {
        $actions = '';
        $actions .= $this->renderAction('milhq_admin_form_field_edit', ['formId' => $this->form->getId(), 'identifier' => $id], 'pencil-simple-line');
        $actions .= $this->renderAction('milhq_admin_form_field_delete', ['formId' => $this->form->getId(), 'identifier' => $id], 'x');
        return $actions;
    }
}
