<?php

declare(strict_types=1);

namespace Forumify\Milhq\Admin\Component\RecordTable;

use DateTimeInterface;
use Forumify\Core\Component\Table\AbstractDoctrineTable;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

abstract class AbstractAdminRecordTable extends AbstractDoctrineTable
{
    public function __construct(
        protected readonly UrlGeneratorInterface $urlGenerator,
    ) {
        $this->sort = ['createdAt' => self::SORT_DESC];
    }

    abstract protected function addRecordColumns(): static;

    abstract protected function getRecordType(): string;

    protected function buildTable(): void
    {
        $this
            ->addDateColumn()
            ->addColumn('soldier', [
                'field' => 'soldier.name',
            ])
            ->addRecordColumns()
            ->addColumn('actions', [
                'field' => 'id',
                'label' => '',
                'renderer' => $this->renderActions(...),
                'searchable' => false,
                'sortable' => false,
            ])
        ;
    }

    protected function addDateColumn(): static
    {
        $this->addColumn('createdAt', [
            'field' => 'createdAt',
            'label' => 'Date',
            'renderer' => fn(DateTimeInterface $date) => $date->format('Y-m-d H:i:s'),
            'searchable' => false,
        ]);

        return $this;
    }

    private function renderActions(int $id): string
    {
        $recordType = $this->getRecordType();

        return $this->security->isGranted("forumify-milhq.admin.records.$recordType.delete")
            ? $this->renderAction("milhq_admin_{$recordType}_delete", ['identifier' => $id], 'x')
            : '';
    }
}
