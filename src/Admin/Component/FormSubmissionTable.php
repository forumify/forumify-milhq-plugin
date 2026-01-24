<?php

declare(strict_types=1);

namespace Forumify\Milhq\Admin\Component;

use DateTimeInterface;
use Doctrine\ORM\QueryBuilder;
use Forumify\Core\Component\Table\AbstractDoctrineTable;
use Forumify\Milhq\Entity\FormSubmission;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Twig\Environment;

#[AsLiveComponent('Milhq\\SubmissionTable', '@Forumify/components/table/table.html.twig')]
#[IsGranted('forumify-milhq.admin.submissions.view')]
class FormSubmissionTable extends AbstractDoctrineTable
{
    #[LiveProp]
    public ?int $form = null;

    public function __construct(private readonly Environment $twig)
    {
        $this->sort = ['createdAt' => self::SORT_DESC];
    }

    protected function getEntityClass(): string
    {
        return FormSubmission::class;
    }

    protected function buildTable(): void
    {
        $this
            ->addColumn('createdAt', [
                'field' => 'createdAt',
                'label' => 'Created At',
                'renderer' => fn(?DateTimeInterface $date) => $date?->format('Y-m-d H:i:s') ?? '',
                'searchable' => false,
            ])
            ->addColumn('soldier', [
                'field' => 'soldier?.name',
                'label' => 'Name',
            ])
            ->addColumn('form', [
                'field' => 'form?.name',
                'label' => 'Form',
            ])
            ->addColumn('status', [
                'field' => 'status?.name',
                'renderer' => fn($_, FormSubmission $submission) => $submission->getStatus() !== null
                    ? $this->twig->render('@ForumifyMilhqPlugin/frontend/roster/components/status.html.twig', [
                        'class' => 'text-small',
                        'status' => $submission->getStatus(),
                    ])
                    : '',
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
        $actions = $this->renderAction('milhq_admin_submission_view', ['id' => $id], 'eye');
        if ($this->security->isGranted('forumify-milhq.admin.submissions.delete')) {
            $actions .= $this->renderAction('milhq_admin_submission_delete', ['id' => $id], 'x');
        }

        return $actions;
    }

    protected function getQuery(array $search): QueryBuilder
    {
        $qb = parent::getQuery($search);
        if ($this->form !== null) {
            $qb->andWhere('e.form = :form')
                ->setParameter('form', $this->form)
            ;
        }

        return $qb;
    }
}
