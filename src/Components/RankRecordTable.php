<?php

declare(strict_types=1);

namespace Forumify\Milhq\Components;

use Forumify\Milhq\Entity\Record\RankRecord;
use Symfony\Component\Asset\Packages;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

#[AsLiveComponent('Milhq\\RankRecordTable', '@ForumifyMilhqPlugin/frontend/components/record_table.html.twig')]
class RankRecordTable extends AbstractRecordTable
{
    protected array $searchFields = ['rank.name', 'type'];

    public function __construct(private readonly Packages $packages)
    {
    }

    protected function getEntityClass(): string
    {
        return RankRecord::class;
    }

    protected function buildTable(): void
    {
        $this
            ->addDateColumn()
            ->addColumn('rank', [
                'class' => 'flex items-center text-small',
                'field' => 'rank.name',
                'renderer' => $this->renderRank(...),
                'searchable' => false,
                'sortable' => false,
            ])
            ->addColumn('type', [
                'class' => 'text-left text-small',
                'field' => 'type',
                'renderer' => fn (string $type) => $this->translator->trans("milhq.rank.type.$type"),
                'searchable' => false,
            ])
            ->addDocumentColumn(true, 'rank');
    }

    private function renderRank(string $rankName, RankRecord $record): string
    {
        $image = $record->getRank()->getImage() ?? null;
        if ($image !== null) {
            $image = $this->packages->getUrl($image, 'milhq.asset');
        }
        $image = $image ? "<img src='$image' width='100%' height='auto' style='max-width: 24px; max-height: 24px;'>" : '';

        return "<div class='mr-1 flex justify-center items-center' style='width: 24px; height: 24px'>$image</div>" . $rankName;
    }
}
