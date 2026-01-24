<?php

declare(strict_types=1);

namespace Forumify\Milhq\Admin\Controller;

use Forumify\Admin\Crud\AbstractCrudController;
use Forumify\Milhq\Admin\Form\PositionType;
use Forumify\Milhq\Entity\Position;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/positions', 'position')]
#[IsGranted('forumify-milhq.admin.organization.positions.view')]
class PositionController extends AbstractCrudController
{
    protected ?string $permissionView = 'forumify-milhq.admin.organization.positions.view';
    protected ?string $permissionCreate = 'forumify-milhq.admin.organization.positions.create';
    protected ?string $permissionEdit = 'forumify-milhq.admin.organization.positions.manage';
    protected ?string $permissionDelete = 'forumify-milhq.admin.organization.positions.delete';

    protected function getTranslationPrefix(): string
    {
        return 'milhq.' . parent::getTranslationPrefix();
    }

    protected function getEntityClass(): string
    {
        return Position::class;
    }

    protected function getTableName(): string
    {
        return 'Milhq\\PositionTable';
    }

    protected function getForm(?object $data): FormInterface
    {
        return $this->createForm(PositionType::class, $data);
    }
}
