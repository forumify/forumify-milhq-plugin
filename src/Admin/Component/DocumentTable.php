<?php

declare(strict_types=1);

namespace Forumify\Milhq\Admin\Component;

use Forumify\Core\Component\Table\AbstractDoctrineTable;
use Forumify\Milhq\Entity\Document;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

use function Symfony\Component\String\u;

#[AsLiveComponent('Milhq\\DocumentTable', '@Forumify/components/table/table.html.twig')]
#[IsGranted('forumify-milhq.admin.organization.documents.view')]
class DocumentTable extends AbstractDoctrineTable
{
    protected function getEntityClass(): string
    {
        return Document::class;
    }

    protected function buildTable(): void
    {
        $this
            ->addColumn('name', [
                'field' => 'name',
                'renderer' => $this->renderName(...),
            ])
            ->addColumn('actions', [
                'field' => 'id',
                'label' => '',
                'searchable' => false,
                'sortable' => false,
                'renderer' => $this->renderActions(...),
            ])
        ;
    }

    private function renderName(string $name, Document $document): string
    {
        $description = u(strip_tags($document->getDescription()))->truncate(1000, '...', true);
        return "<p>$name</p><p class='text-small'>{$description}</p>";
    }

    private function renderActions(int $id): string
    {
        $actions = '';

        if ($this->security->isGranted('forumify-milhq.admin.organization.documents.manage')) {
            $actions .= $this->renderAction('milhq_admin_document_edit', ['identifier' => $id], 'pencil-simple-line');
        }
        if ($this->security->isGranted('forumify-milhq.admin.organization.documents.delete')) {
            $actions .= $this->renderAction('milhq_admin_document_delete', ['identifier' => $id], 'x');
        }

        return $actions;
    }
}
