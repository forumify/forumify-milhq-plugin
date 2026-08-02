<?php

declare(strict_types=1);

namespace Forumify\Milhq\Admin\Component;

use Doctrine\Common\Collections\Collection;
use Forumify\Core\Repository\AbstractRepository;
use Forumify\Milhq\Entity\GroupInterface;
use Forumify\Milhq\Entity\RankGroup;
use Forumify\Milhq\Repository\RankGroupRepository;
use Forumify\Milhq\Repository\RankRepository;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

#[AsLiveComponent('Milhq\\RankEditor', '@ForumifyMilhqPlugin/admin/components/group_editor/group_editor.html.twig')]
#[IsGranted('milhq.admin.organization.ranks.view')]
class RankEditor extends AbstractGroupEditor
{
    public function __construct(
        private readonly RankRepository $rankRepository,
        private readonly RankGroupRepository $rankGroupRepository,
    ) {
    }

    protected function getGroupRepository(): AbstractRepository
    {
        return $this->rankGroupRepository;
    }

    protected function getItemRepository(): AbstractRepository
    {
        return $this->rankRepository;
    }

    public function getItems(GroupInterface $group): Collection
    {
        \assert($group instanceof RankGroup);
        return $group->getRanks();
    }

    public function getGroupRoutePrefix(): string
    {
        return 'milhq_admin_rank_group';
    }

    public function getItemRoutePrefix(): string
    {
        return 'milhq_admin_rank';
    }

    public function getGroupTranslationPrefix(): string
    {
        return 'milhq.admin.rank_group.crud.';
    }

    public function getItemTranslationPrefix(): string
    {
        return 'milhq.admin.rank.crud.';
    }

    public function getManagePermission(): string
    {
        return 'milhq.admin.organization.ranks.manage';
    }
}
