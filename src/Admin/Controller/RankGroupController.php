<?php

declare(strict_types=1);

namespace Forumify\Milhq\Admin\Controller;

use Forumify\Admin\Crud\AbstractCrudController;
use Forumify\Milhq\Admin\Form\RankGroupType;
use Forumify\Milhq\Entity\RankGroup;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Response;

/**
 * @extends AbstractCrudController<RankGroup>
 */
#[Route('/ranks-group', 'rank_group')]
#[IsGranted('milhq.admin.organization.ranks.view')]
class RankGroupController extends AbstractCrudController
{
    protected ?string $permissionView = 'milhq.admin.organization.ranks.view';
    protected ?string $permissionCreate = 'milhq.admin.organization.ranks.create';
    protected ?string $permissionEdit = 'milhq.admin.organization.ranks.manage';
    protected ?string $permissionDelete = 'milhq.admin.organization.ranks.delete';

    protected function getTranslationPrefix(): string
    {
        return 'milhq.' . parent::getTranslationPrefix();
    }

    protected function getEntityClass(): string
    {
        return RankGroup::class;
    }

    protected function getTableName(): string
    {
        return 'Milhq\\RankEditor';
    }

    protected function getForm(?object $data): FormInterface
    {
        return $this->createForm(RankGroupType::class, $data);
    }

    #[Route('', '_list')]
    public function list(): Response
    {
        return $this->redirectToRoute('milhq_admin_rank_list');
    }
}
