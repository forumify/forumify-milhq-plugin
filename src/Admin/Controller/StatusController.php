<?php

declare(strict_types=1);

namespace Forumify\Milhq\Admin\Controller;

use Forumify\Admin\Crud\AbstractCrudController;
use Forumify\Milhq\Admin\Form\StatusType;
use Forumify\Milhq\Entity\Status;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @extends AbstractCrudController<Status>
 */
#[Route('/statuses', 'status')]
#[IsGranted('milhq.admin.organization.view')]
class StatusController extends AbstractCrudController
{
    protected ?string $permissionView = 'milhq.admin.organization.statuses.view';
    protected ?string $permissionCreate = 'milhq.admin.organization.statuses.create';
    protected ?string $permissionEdit = 'milhq.admin.organization.statuses.manage';
    protected ?string $permissionDelete = 'milhq.admin.organization.statuses.delete';

    protected function getTranslationPrefix(): string
    {
        return 'milhq.' . parent::getTranslationPrefix();
    }

    protected function getEntityClass(): string
    {
        return Status::class;
    }

    protected function getTableName(): string
    {
        return 'Milhq\\StatusTable';
    }

    protected function getForm(?object $data): FormInterface
    {
        return $this->createForm(StatusType::class, $data);
    }
}
