<?php

declare(strict_types=1);

namespace Forumify\Milhq\Admin\Controller;

use Forumify\Admin\Crud\AbstractCrudController;
use Forumify\Milhq\Admin\Form\FormFieldType;
use Forumify\Milhq\Entity\Form;
use Forumify\Milhq\Entity\FormField;
use Forumify\Milhq\Repository\FormRepository;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @extends AbstractCrudController<FormField>
 */
#[Route('/forms/{formId}/fields', 'form_field')]
#[IsGranted('forumify-milhq.admin.organization.forms.manage')]
class FormFieldController extends AbstractCrudController
{
    protected string $listTemplate = '@ForumifyMilhqPlugin/admin/forms/field_list.html.twig';
    protected string $formTemplate = '@ForumifyMilhqPlugin/admin/forms/field_form.html.twig';
    protected string $deleteTemplate = '@ForumifyMilhqPlugin/admin/forms/field_delete.html.twig';

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
        return FormField::class;
    }

    protected function getTableName(): string
    {
        return 'Milhq\\FormFieldTable';
    }

    protected function getForm(?object $data): FormInterface
    {
        if ($data === null) {
            $data = new FormField();
            $data->setForm($this->getParent());
        }
        return $this->createForm(FormFieldType::class, $data);
    }

    protected function templateParams(array $params = []): array
    {
        return parent::templateParams([
            'parentForm' => $this->getParent(),
            ...$params,
        ]);
    }

    protected function redirectAfterSave(mixed $entity, bool $isNew): Response
    {
        return $this->redirectToRoute($this->getRoute('list'), ['formId' => $this->getParent()->getId()]);
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
