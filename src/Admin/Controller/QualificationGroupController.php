<?php

declare(strict_types=1);

namespace Forumify\Milhq\Admin\Controller;

use Forumify\Admin\Crud\AbstractCrudController;
use Forumify\Milhq\Admin\Form\QualificationGroupType;
use Forumify\Milhq\Entity\QualificationGroup;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @extends AbstractCrudController<QualificationGroup>
 */
#[Route('/qualifications-group', 'qualification_group')]
#[IsGranted('milhq.admin.organization.qualifications.view')]
class QualificationGroupController extends AbstractCrudController
{
    protected ?string $permissionView = 'milhq.admin.organization.qualifications.view';
    protected ?string $permissionCreate = 'milhq.admin.organization.qualifications.create';
    protected ?string $permissionEdit = 'milhq.admin.organization.qualifications.manage';
    protected ?string $permissionDelete = 'milhq.admin.organization.qualifications.delete';

    protected function getTranslationPrefix(): string
    {
        return 'milhq.' . parent::getTranslationPrefix();
    }

    protected function getEntityClass(): string
    {
        return QualificationGroup::class;
    }

    protected function getTableName(): string
    {
        return 'Milhq\\QualificationEditor';
    }

    protected function getForm(?object $data): FormInterface
    {
        return $this->createForm(QualificationGroupType::class, $data);
    }

    #[Route('', '_list')]
    public function list(): Response
    {
        return $this->redirectToRoute('milhq_admin_qualification_list');
    }
}
