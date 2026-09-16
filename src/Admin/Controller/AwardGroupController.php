<?php

declare(strict_types=1);

namespace Forumify\Milhq\Admin\Controller;

use Forumify\Admin\Crud\AbstractCrudController;
use Forumify\Milhq\Admin\Form\AwardGroupType;
use Forumify\Milhq\Entity\AwardGroup;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @extends AbstractCrudController<AwardGroup>
 */
#[Route('/awards-group', 'award_group')]
#[IsGranted('milhq.admin.organization.awards.view')]
class AwardGroupController extends AbstractCrudController
{
    protected ?string $permissionView = 'milhq.admin.organization.awards.view';
    protected ?string $permissionCreate = 'milhq.admin.organization.awards.create';
    protected ?string $permissionEdit = 'milhq.admin.organization.awards.manage';
    protected ?string $permissionDelete = 'milhq.admin.organization.awards.delete';

    protected function getTranslationPrefix(): string
    {
        return 'milhq.' . parent::getTranslationPrefix();
    }

    protected function getEntityClass(): string
    {
        return AwardGroup::class;
    }

    protected function getTableName(): string
    {
        return 'Milhq\\AwardEditor';
    }

    protected function getForm(?object $data): FormInterface
    {
        return $this->createForm(AwardGroupType::class, $data);
    }

    #[Route('', '_list')]
    public function list(): Response
    {
        return $this->redirectToRoute('milhq_admin_award_list');
    }
}
