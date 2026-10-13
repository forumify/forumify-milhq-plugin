<?php

declare(strict_types=1);

namespace Forumify\Milhq\Admin\Component;

use Forumify\Milhq\Entity\Qualification;
use Forumify\Milhq\Entity\QualificationGroup;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * @extends AbstractGroupEditor<QualificationGroup, Qualification>
 */
#[AsLiveComponent('Milhq\\QualificationEditor', '@ForumifyMilhqPlugin/admin/components/group_editor/qualification_editor.html.twig')]
#[IsGranted('milhq.admin.organization.qualifications.view')]
class QualificationEditor extends AbstractGroupEditor
{
    protected function getGroupEntityClass(): string
    {
        return QualificationGroup::class;
    }

    protected function getItemEntityClass(): string
    {
        return Qualification::class;
    }

    public function getGroupRoutePrefix(): string
    {
        return 'milhq_admin_qualification_group';
    }

    public function getItemRoutePrefix(): string
    {
        return 'milhq_admin_qualification';
    }

    public function getGroupTranslationPrefix(): string
    {
        return 'milhq.admin.qualification_group.crud.';
    }

    public function getItemTranslationPrefix(): string
    {
        return 'milhq.admin.qualification.crud.';
    }

    public function getManagePermission(): string
    {
        return 'milhq.admin.organization.qualifications.manage';
    }
}
