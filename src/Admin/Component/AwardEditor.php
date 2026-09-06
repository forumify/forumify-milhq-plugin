<?php

declare(strict_types=1);

namespace Forumify\Milhq\Admin\Component;

use Forumify\Milhq\Entity\Award;
use Forumify\Milhq\Entity\AwardGroup;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * @extends AbstractGroupEditor<AwardGroup, Award>
 */
#[AsLiveComponent('Milhq\\AwardEditor', '@ForumifyMilhqPlugin/admin/components/group_editor/award_editor.html.twig')]
#[IsGranted('milhq.admin.organization.awards.view')]
class AwardEditor extends AbstractGroupEditor
{
    protected function getGroupEntityClass(): string
    {
        return AwardGroup::class;
    }

    protected function getItemEntityClass(): string
    {
        return Award::class;
    }

    public function getGroupRoutePrefix(): string
    {
        return 'milhq_admin_award_group';
    }

    public function getItemRoutePrefix(): string
    {
        return 'milhq_admin_award';
    }

    public function getGroupTranslationPrefix(): string
    {
        return 'milhq.admin.award_group.crud.';
    }

    public function getItemTranslationPrefix(): string
    {
        return 'milhq.admin.award.crud.';
    }

    public function getManagePermission(): string
    {
        return 'milhq.admin.organization.awards.manage';
    }
}
