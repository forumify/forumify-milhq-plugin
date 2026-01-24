<?php

declare(strict_types=1);

namespace Forumify\Milhq\Admin\Controller;

use Forumify\Admin\Crud\AbstractCrudController;
use Forumify\Milhq\Admin\Form\DocumentType;
use Forumify\Milhq\Entity\Document;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @extends AbstractCrudController<Document>
 */
#[Route('/documents', 'document')]
class DocumentController extends AbstractCrudController
{
    protected ?string $permissionView = 'forumify-milhq.admin.organization.documents.view';
    protected ?string $permissionCreate = 'forumify-milhq.admin.organization.documents.create';
    protected ?string $permissionEdit = 'forumify-milhq.admin.organization.documents.manage';
    protected ?string $permissionDelete = 'forumify-milhq.admin.organization.documents.delete';

    protected function getTranslationPrefix(): string
    {
        return 'milhq.' . parent::getTranslationPrefix();
    }

    protected function getEntityClass(): string
    {
        return Document::class;
    }

    protected function getTableName(): string
    {
        return 'Milhq\\DocumentTable';
    }

    protected function getForm(?object $data): FormInterface
    {
        return $this->createForm(DocumentType::class, $data);
    }
}
