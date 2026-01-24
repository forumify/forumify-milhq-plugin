<?php

declare(strict_types=1);

namespace Forumify\Milhq\Admin\Controller;

use Forumify\Admin\Crud\AbstractCrudController;
use Forumify\Milhq\Admin\Form\UnitType;
use Forumify\Milhq\Entity\Unit;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/units', 'unit')]
#[IsGranted('forumify-milhq.admin.organization.units.view')]
class UnitController extends AbstractCrudController
{
    protected ?string $permissionView = 'forumify-milhq.admin.organization.units.view';
    protected ?string $permissionCreate = 'forumify-milhq.admin.organization.units.create';
    protected ?string $permissionEdit = 'forumify-milhq.admin.organization.units.manage';
    protected ?string $permissionDelete = 'forumify-milhq.admin.organization.units.delete';

    protected function getTranslationPrefix(): string
    {
        return 'milhq.' . parent::getTranslationPrefix();
    }

    protected function getEntityClass(): string
    {
        return Unit::class;
    }

    protected function getTableName(): string
    {
        return 'Milhq\\UnitTable';
    }

    protected function getForm(?object $data): FormInterface
    {
        return $this->createForm(UnitType::class, $data);
    }
}
