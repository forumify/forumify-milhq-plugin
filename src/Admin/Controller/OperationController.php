<?php

declare(strict_types=1);

namespace Forumify\Milhq\Admin\Controller;

use Forumify\Admin\Crud\AbstractCrudController;
use Forumify\Milhq\Admin\Form\OperationType;
use Forumify\Milhq\Entity\Operation;
use Forumify\Plugin\Attribute\PluginVersion;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Routing\Attribute\Route;

#[PluginVersion('forumify/forumify-milhq-plugin', 'premium')]
#[Route('/operations', 'operations')]
class OperationController extends AbstractCrudController
{
    protected ?string $permissionView = 'forumify-milhq.admin.operations.view';
    protected ?string $permissionCreate = 'forumify-milhq.admin.operations.manage';
    protected ?string $permissionEdit = 'forumify-milhq.admin.operations.manage';
    protected ?string $permissionDelete = 'forumify-milhq.admin.operations.delete';

    protected function getTranslationPrefix(): string
    {
        return 'milhq.' . parent::getTranslationPrefix();
    }

    protected function getEntityClass(): string
    {
        return Operation::class;
    }

    protected function getTableName(): string
    {
        return 'Milhq\\OperationTable';
    }

    protected function getForm(?object $data): FormInterface
    {
        return $this->createForm(OperationType::class, $data);
    }
}
