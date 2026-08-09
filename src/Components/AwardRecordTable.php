<?php

declare(strict_types=1);

namespace Forumify\Milhq\Components;

use Doctrine\ORM\QueryBuilder;
use Forumify\Milhq\Entity\Record\AwardRecord;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Symfony\Component\Asset\Packages;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

#[AsLiveComponent('Milhq\\AwardRecordTable', '@ForumifyMilhqPlugin/frontend/components/record_table.html.twig')]
class AwardRecordTable extends AbstractRecordTable
{
    protected array $searchFields = ['award.name'];

    public function __construct(
        private readonly Packages $packages,
        private readonly CacheManager $liip,
    ) {
    }

    protected function getEntityClass(): string
    {
        return AwardRecord::class;
    }

    protected function buildTable(): void
    {
        $this
            ->addDateColumn()
            ->addColumn('award', [
                'class' => 'text-small',
                'field' => 'award.name',
                'renderer' => $this->renderAward(...),
                'searchable' => false,
                'sortable' => false,
            ])
            ->addDocumentColumn(true, 'award');
    }

    private function renderAward(?string $awardName, AwardRecord $record): string
    {
        $tier = $record->getTier();

        $image = $tier !== null ? $tier->image : null;
        $image ??= $record->getAward()->getImage();
        $image = $image
            ? $this->liip->getBrowserPath($this->packages->getUrl($image, 'milhq.asset'), 'milhq_small')
            : null;
        $image = $image ? "<img src='$image' width='100%' height='auto' style='max-width: 24px; max-height: 24px;'>" : '';

        $awardName = $awardName ?? 'Unknown';
        $label = $tier !== null ? "$awardName: {$tier->name}" : $awardName;

        return "<div class='w-100 flex items-center gap-2'>$image $label</div>";
    }

    protected function getQuery(array $search): QueryBuilder
    {
        return parent::getQuery($search)
            ->addSelect('award')
            ->addSelect('tier');
    }

    protected function addSearchToQuery(QueryBuilder $qb): QueryBuilder
    {
        $qb->leftJoin('e.tier', 'tier');

        $query = trim($this->query);
        if ($query === '') {
            return $qb;
        }

        return $qb
            ->andWhere($qb->expr()->orX(
                'award.name LIKE :search',
                'tier.name LIKE :search',
            ))
            ->setParameter('search', "%$query%");
    }
}
