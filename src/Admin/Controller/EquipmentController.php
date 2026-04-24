<?php

namespace Forumify\Milhq\Admin\Controller;

use Forumify\Admin\Crud\AbstractCrudController;
use Forumify\Milhq\Admin\Form\EquipmentFormType;
use Forumify\Milhq\Entity\Equipment;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @extends AbstractCrudController<Equipment>
 */
#[Route('/equipment', 'equipment')]
#[IsGranted('milhq.admin.organization.equipment.view')]
class EquipmentController extends AbstractCrudController
{
    protected ?string $permissionView = 'milhq.admin.organization.equipment.view';
    protected ?string $permissionCreate = 'milhq.admin.organization.equipment.create';
    protected ?string $permissionEdit = 'milhq.admin.organization.equipment.manage';
    protected ?string $permissionDelete = 'milhq.admin.organization.equipment.delete';

    protected function getTranslationPrefix(): string
    {
        return 'milhq.' . parent::getTranslationPrefix();
    }

    protected function getEntityClass(): string
    {
        return Equipment::class;
    }

    protected function getTableName(): string
    {
        return 'Milhq\\EquipmentTable';
    }

    protected function getForm(?object $data): FormInterface
    {
        return $this->createForm(EquipmentFormType::class, $data);
    }
}
