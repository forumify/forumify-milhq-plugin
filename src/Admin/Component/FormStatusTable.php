<?php

declare(strict_types=1);

namespace Forumify\Milhq\Admin\Component;

use Doctrine\ORM\QueryBuilder;
use Forumify\Core\Component\Table\AbstractDoctrineTable;
use Forumify\Core\Entity\SortableEntityInterface;
use Forumify\Milhq\Entity\Form;
use Forumify\Milhq\Entity\FormStatus;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Twig\Environment;

#[AsLiveComponent('Milhq\\FormStatusTable', '@Forumify/components/table/table.html.twig')]
#[IsGranted('milhq.admin.organization.forms.manage')]
class FormStatusTable extends AbstractDoctrineTable
{
    #[LiveProp]
    public Form $form;

    protected ?string $permissionReorder = 'milhq.admin.organization.forms.manage';

    public function __construct(private readonly Environment $twig)
    {
        $this->sort = ['position' => self::SORT_ASC];
    }

    protected function getEntityClass(): string
    {
        return FormStatus::class;
    }

    protected function buildTable(): void
    {
        $this
            ->addPositionColumn()
            ->addColumn('name', [
                'field' => 'name',
            ])
            ->addColumn('appearance', [
                'renderer' => fn ($_, FormStatus $status) => $this->twig->render('@ForumifyMilhqPlugin/frontend/roster/components/status.html.twig', ['status' => $status]),
                'searchable' => false,
                'sortable' => false,
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
        $actions .= $this->renderAction('milhq_admin_form_status_edit', ['formId' => $this->form->getId(), 'identifier' => $id], 'pencil-simple-line');
        $actions .= $this->renderAction('milhq_admin_form_status_delete', ['formId' => $this->form->getId(), 'identifier' => $id], 'x');
        return $actions;
    }
}
