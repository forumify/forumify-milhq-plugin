<?php

declare(strict_types=1);

namespace Forumify\Milhq\Admin\Component;

use Doctrine\ORM\QueryBuilder;
use Forumify\Core\Component\Table\AbstractDoctrineTable;
use Forumify\Core\Entity\SortableEntityInterface;
use Forumify\Milhq\Entity\AwardTier;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

#[AsLiveComponent('Milhq\\AwardTierTable', '@Forumify/components/table/table.html.twig')]
#[IsGranted('milhq.admin.organization.awards.manage')]
class AwardTierTable extends AbstractDoctrineTable
{
    #[LiveProp]
    public int $awardId;

    protected ?string $permissionReorder = 'milhq.admin.organization.awards.manage';

    public function __construct(
        private readonly Packages $packages,
        private readonly CacheManager $liip,
    ) {
    }

    protected function getEntityClass(): string
    {
        return AwardTier::class;
    }

    protected function buildTable(): void
    {
        $this
            ->addPositionColumn()
            ->addColumn('name', [
                'field' => 'name',
                'searchable' => false,
                'renderer' => $this->renderName(...),
            ])
            ->addActionColumn($this->renderActions(...))
        ;
    }

    protected function getQuery(array $search): QueryBuilder
    {
        return parent::getQuery($search)
            ->andWhere('e.parent = :award')
            ->setParameter('award', $this->awardId)
        ;
    }

    protected function reorderItem(SortableEntityInterface $entity, string $direction): void
    {
        $this->repository->reorder($entity, $direction, fn (QueryBuilder $qb) => $qb
            ->andWhere('e.parent = :award')
            ->setParameter('award', $this->awardId));
    }

    private function renderName(string $name, AwardTier $tier): string
    {
        $img = $tier->image
            ? $this->liip->getBrowserPath($this->packages->getUrl($tier->image, 'milhq.asset'), 'milhq_small')
            : '';

        if ($img) {
            $img = "<img src='$img' alt='{$tier->name} image' />";
        }

        return "<span class='flex items-center gap-2'>$img<span>$name</span></span>";
    }

    private function renderActions(int $id): string
    {
        $actions = '';
        $actions .= $this->renderAction('milhq_admin_award_tier_edit', ['id' => $id], 'pencil-simple-line');
        $actions .= $this->renderAction('milhq_admin_award_tier_delete', ['id' => $id], 'x');
        return $actions;
    }
}
