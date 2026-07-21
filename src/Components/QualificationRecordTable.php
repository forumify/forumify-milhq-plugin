<?php

declare(strict_types=1);

namespace Forumify\Milhq\Components;

use Doctrine\ORM\QueryBuilder;
use Forumify\Milhq\Entity\Record\QualificationRecord;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Symfony\Component\Asset\Packages;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

#[AsLiveComponent('Milhq\\QualificationRecordTable', '@ForumifyMilhqPlugin/frontend/components/record_table.html.twig')]
class QualificationRecordTable extends AbstractRecordTable
{
    protected array $searchFields = ['qualification.name'];

    public function __construct(
        private readonly Packages $packages,
        private readonly CacheManager $liip,
    ) {
    }

    protected function getEntityClass(): string
    {
        return QualificationRecord::class;
    }

    protected function buildTable(): void
    {
        $this
            ->addDateColumn()
            ->addColumn('qualification', [
                'class' => 'text-small',
                'field' => 'qualification.name',
                'renderer' => $this->renderQualification(...),
                'searchable' => false,
                'sortable' => false,
            ])
            ->addDocumentColumn(true, 'qualification');
    }

    private function renderQualification(string $qualificationName, QualificationRecord $record): string
    {
        $tier = $record->getTier();

        $image = $tier !== null ? $tier->image : null;
        $image ??= $record->getQualification()->getImage();
        $image = $image
            ? $this->liip->getBrowserPath($this->packages->getUrl($image, 'milhq.asset'), 'milhq_small')
            : null;
        $image = $image ? "<img src='$image' width='100%' height='auto' style='max-width: 24px; max-height: 24px;'>" : '';

        $label = $tier !== null ? "$qualificationName: {$tier->name}" : $qualificationName;

        return "<div class='w-100 flex items-center gap-2'>$image $label</div>";
    }

    protected function getQuery(array $search): QueryBuilder
    {
        return parent::getQuery($search)
            ->addSelect('qualification')
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
                'qualification.name LIKE :search',
                'tier.name LIKE :search',
            ))
            ->setParameter('search', "%$query%");
    }
}
