<?php

declare(strict_types=1);

namespace Forumify\Milhq\Admin\Component;

use Forumify\Milhq\Entity\Rank;
use Forumify\Milhq\Entity\RankGroup;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * @extends AbstractGroupEditor<RankGroup, Rank>
 */
#[AsLiveComponent('Milhq\\RankEditor', '@ForumifyMilhqPlugin/admin/components/group_editor/group_editor.html.twig')]
#[IsGranted('milhq.admin.organization.ranks.view')]
class RankEditor extends AbstractGroupEditor
{
    protected function getGroupEntityClass(): string
    {
        return RankGroup::class;
    }

    protected function getItemEntityClass(): string
    {
        return Rank::class;
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
