<?php

declare(strict_types=1);

namespace Forumify\Milhq\Admin\Controller;

use Forumify\Admin\Crud\AbstractCrudController;
use Forumify\Milhq\Admin\Form\FormStatusType;
use Forumify\Milhq\Entity\Form;
use Forumify\Milhq\Entity\FormStatus;
use Forumify\Milhq\Repository\FormRepository;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @extends AbstractCrudController<FormStatus>
 */
#[Route('/forms/{formId}/statuses', 'form_status')]
#[IsGranted('milhq.admin.organization.forms.manage')]
class FormStatusController extends AbstractCrudController
{
    protected string $listTemplate = '@ForumifyMilhqPlugin/admin/forms/nested_list.html.twig';
    protected string $formTemplate = '@ForumifyMilhqPlugin/admin/forms/status_form.html.twig';
    protected string $deleteTemplate = '@ForumifyMilhqPlugin/admin/forms/nested_delete.html.twig';

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly FormRepository $formRepository,
    ) {
    }

    protected function getTranslationPrefix(): string
    {
        return 'milhq.' . parent::getTranslationPrefix();
    }

    protected function getEntityClass(): string
    {
        return FormStatus::class;
    }

    protected function getTableName(): string
    {
        return 'Milhq\\FormStatusTable';
    }

    protected function getForm(?object $data): FormInterface
    {
        if ($data === null) {
            $data = new FormStatus();
            $data->setForm($this->getParent());
        }
        return $this->createForm(FormStatusType::class, $data);
    }

    protected function templateParams(array $params = []): array
    {
        return parent::templateParams([
            'parentForm' => $this->getParent(),
            ...$params,
        ]);
    }

    private function getParent(): Form
    {
        $request = $this->requestStack->getCurrentRequest();
        $form = $this->formRepository->find($request->attributes->get('formId'));
        if ($form === null) {
            throw $this->createNotFoundException();
        }

        return $form;
    }

    protected function redirectToRoute(string $route, array $parameters = [], int $status = 302): RedirectResponse
    {
        $parameters['formId'] = $this->getParent()->getId();
        return parent::redirectToRoute($route, $parameters, $status);
    }
}
