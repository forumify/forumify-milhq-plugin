<?php

declare(strict_types=1);

namespace Forumify\Milhq\Admin\Controller;

use Forumify\Admin\Crud\AbstractCrudController;
use Forumify\Milhq\Admin\Form\RosterType;
use Forumify\Milhq\Entity\Roster;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @extends AbstractCrudController<Roster>
 */
#[Route('/rosters', 'roster')]
#[IsGranted('forumify-milhq.admin.organization.rosters.view')]
class RosterController extends AbstractCrudController
{
    protected ?string $permissionView = 'forumify-milhq.admin.organization.rosters.view';
    protected ?string $permissionCreate = 'forumify-milhq.admin.organization.rosters.create';
    protected ?string $permissionEdit = 'forumify-milhq.admin.organization.rosters.manage';
    protected ?string $permissionDelete = 'forumify-milhq.admin.organization.rosters.delete';

    protected function getTranslationPrefix(): string
    {
        return 'milhq.' . parent::getTranslationPrefix();
    }

    protected function getEntityClass(): string
    {
        return Roster::class;
    }

    protected function getTableName(): string
    {
        return 'Milhq\\RosterTable';
    }

    protected function getForm(?object $data): FormInterface
    {
        return $this->createForm(RosterType::class, $data);
    }
}
