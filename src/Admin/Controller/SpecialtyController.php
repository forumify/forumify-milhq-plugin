<?php

declare(strict_types=1);

namespace Forumify\Milhq\Admin\Controller;

use Forumify\Admin\Crud\AbstractCrudController;
use Forumify\Milhq\Admin\Form\SpecialtyType;
use Forumify\Milhq\Entity\Specialty;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/specialties', 'specialty')]
#[IsGranted('forumify-milhq.admin.organization.specialties.view')]
class SpecialtyController extends AbstractCrudController
{
    protected ?string $permissionView = 'forumify-milhq.admin.organization.specialties.view';
    protected ?string $permissionCreate = 'forumify-milhq.admin.organization.specialties.create';
    protected ?string $permissionEdit = 'forumify-milhq.admin.organization.specialties.manage';
    protected ?string $permissionDelete = 'forumify-milhq.admin.organization.specialties.delete';

    protected function getTranslationPrefix(): string
    {
        return 'milhq.' . parent::getTranslationPrefix();
    }

    protected function getEntityClass(): string
    {
        return Specialty::class;
    }

    protected function getTableName(): string
    {
        return 'Milhq\\SpecialtyTable';
    }

    protected function getForm(?object $data): FormInterface
    {
        return $this->createForm(SpecialtyType::class, $data);
    }
}
