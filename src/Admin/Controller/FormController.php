<?php

declare(strict_types=1);

namespace Forumify\Milhq\Admin\Controller;

use Forumify\Admin\Crud\AbstractCrudController;
use Forumify\Milhq\Admin\Form\FormType;
use Forumify\Milhq\Entity\Form;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @extends AbstractCrudController<Form>
 */
#[Route('/forms', 'form')]
class FormController extends AbstractCrudController
{
    protected ?string $permissionView = 'milhq.admin.organization.forms.view';
    protected ?string $permissionCreate = 'milhq.admin.organization.forms.create';
    protected ?string $permissionEdit = 'milhq.admin.organization.forms.manage';
    protected ?string $permissionDelete = 'milhq.admin.organization.forms.delete';

    protected function getTranslationPrefix(): string
    {
        return 'milhq.' . parent::getTranslationPrefix();
    }

    protected function getEntityClass(): string
    {
        return Form::class;
    }

    protected function getTableName(): string
    {
        return 'Milhq\\FormTable';
    }

    protected function getForm(?object $data): FormInterface
    {
        return $this->createForm(FormType::class, $data);
    }
}
