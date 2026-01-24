<?php

declare(strict_types=1);

namespace Forumify\Milhq\Admin\Controller;

use Forumify\Admin\Crud\AbstractCrudController;
use Forumify\Milhq\Admin\Form\QualificationType;
use Forumify\Milhq\Entity\Qualification;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/qualifications', 'qualification')]
#[IsGranted('forumify-milhq.admin.organization.view')]
class QualificationController extends AbstractCrudController
{
    protected string $listTemplate = '@ForumifyMilhqPlugin/admin/crud/list.html.twig';

    protected ?string $permissionView = 'forumify-milhq.admin.organization.qualifications.view';
    protected ?string $permissionCreate = 'forumify-milhq.admin.organization.qualifications.create';
    protected ?string $permissionEdit = 'forumify-milhq.admin.organization.qualifications.manage';
    protected ?string $permissionDelete = 'forumify-milhq.admin.organization.qualifications.delete';

    protected function getTranslationPrefix(): string
    {
        return 'milhq.' . parent::getTranslationPrefix();
    }

    protected function getEntityClass(): string
    {
        return Qualification::class;
    }

    protected function getTableName(): string
    {
        return 'Milhq\\QualificationTable';
    }

    protected function getForm(?object $data): FormInterface
    {
        return $this->createForm(QualificationType::class, $data, [
            'image_required' => $data === null,
        ]);
    }
}
