<?php

declare(strict_types=1);

namespace Forumify\Milhq\Admin\Controller;

use Forumify\Admin\Crud\AbstractCrudController;
use Forumify\Milhq\Admin\Form\RankType;
use Forumify\Milhq\Entity\Rank;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @extends AbstractCrudController<Rank>
 */
#[Route('/ranks', 'rank')]
#[IsGranted('milhq.admin.organization.ranks.view')]
class RankController extends AbstractCrudController
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
        return Rank::class;
    }

    protected function getTableName(): string
    {
        return 'Milhq\\RankTable';
    }

    protected function getForm(?object $data): FormInterface
    {
        return $this->createForm(RankType::class, $data, [
            'image_required' => $data === null,
        ]);
    }
}
