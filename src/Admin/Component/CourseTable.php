<?php

declare(strict_types=1);

namespace Forumify\Milhq\Admin\Component;

use Forumify\Core\Component\Table\AbstractDoctrineTable;
use Forumify\Milhq\Entity\Course;
use Forumify\Plugin\Attribute\PluginVersion;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

#[PluginVersion('forumify/forumify-milhq-plugin', 'premium')]
#[AsLiveComponent('Milhq\\CourseTable', '@Forumify/components/table/table.html.twig')]
#[IsGranted('milhq.admin.courses.view')]
class CourseTable extends AbstractDoctrineTable
{
    protected ?string $permissionReorder = 'milhq.admin.courses.manage';

    protected function getEntityClass(): string
    {
        return Course::class;
    }

    protected function buildTable(): void
    {
        $this
            ->addPositionColumn()
            ->addColumn('title', [
                'field' => 'title',
            ])
            ->addColumn('actions', [
                'field' => 'id',
                'label' => '',
                'renderer' => $this->renderActions(...),
                'searchable' => false,
                'sortable' => false,
            ]);
    }

    private function renderActions(int $id, Course $course): string
    {
        $actions = '';
        if ($this->security->isGranted('milhq.admin.courses.manage')) {
            $actions .= $this->renderAction('milhq_admin_courses_edit', ['identifier' => $id], 'pencil-simple-line');
            $actions .= $this->renderAction('forumify_admin_acl', (array)$course->getACLParameters(), 'lock-simple');
        }
        if ($this->security->isGranted('milhq.admin.courses.delete')) {
            $actions .= $this->renderAction('milhq_admin_courses_delete', ['identifier' => $id], 'x');
        }

        return $actions;
    }
}
