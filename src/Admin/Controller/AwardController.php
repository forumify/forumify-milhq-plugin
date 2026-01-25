<?php

declare(strict_types=1);

namespace Forumify\Milhq\Admin\Controller;

use Forumify\Admin\Crud\AbstractCrudController;
use Forumify\Milhq\Admin\Form\AwardType;
use Forumify\Milhq\Entity\Award;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Form\FormInterface;

/**
 * @extends AbstractCrudController<Award>
 */
#[Route('/awards', 'award')]
#[IsGranted('milhq.admin.organization.awards.view')]
class AwardController extends AbstractCrudController
{
    protected string $listTemplate = '@ForumifyMilhqPlugin/admin/crud/list.html.twig';

    protected ?string $permissionView = 'milhq.admin.organization.awards.view';
    protected ?string $permissionCreate = 'milhq.admin.organization.awards.create';
    protected ?string $permissionEdit = 'milhq.admin.organization.awards.manage';
    protected ?string $permissionDelete = 'milhq.admin.organization.awards.delete';

    protected function getTranslationPrefix(): string
    {
        return 'milhq.' . parent::getTranslationPrefix();
    }

    protected function getEntityClass(): string
    {
        return Award::class;
    }

    protected function getTableName(): string
    {
        return 'Milhq\\AwardTable';
    }

    protected function getForm(?object $data): FormInterface
    {
        return $this->createForm(AwardType::class, $data, [
            'image_required' => $data === null,
        ]);
    }
}
