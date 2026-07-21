<?php

declare(strict_types=1);

namespace Forumify\Milhq\Admin\Controller;

use Forumify\Admin\Crud\AbstractCrudController;
use Forumify\Core\Service\MediaService;
use Forumify\Milhq\Admin\Form\QualificationTierType;
use Forumify\Milhq\Admin\Form\QualificationType;
use Forumify\Milhq\Entity\Qualification;
use Forumify\Milhq\Entity\QualificationTier;
use Forumify\Milhq\Repository\QualificationTierRepository;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @extends AbstractCrudController<Qualification>
 */
#[Route('/qualifications', 'qualification')]
#[IsGranted('milhq.admin.organization.view')]
class QualificationController extends AbstractCrudController
{
    protected ?string $permissionView = 'milhq.admin.organization.qualifications.view';
    protected ?string $permissionCreate = 'milhq.admin.organization.qualifications.create';
    protected ?string $permissionEdit = 'milhq.admin.organization.qualifications.manage';
    protected ?string $permissionDelete = 'milhq.admin.organization.qualifications.delete';

    protected string $formTemplate = '@ForumifyMilhqPlugin/admin/qualifications/form.html.twig';

    public function __construct(
        private readonly QualificationTierRepository $qualificationTierRepository,
        private readonly MediaService $mediaService,
        private readonly FilesystemOperator $milhqAssetStorage,
    ) {
    }

    protected function getTranslationPrefix(): string
    {
        return 'milhq.' . parent::getTranslationPrefix();
    }

    protected function getEntityClass(): string
    {
        return Qualification::class;
    }

    protected function getTableName(): string
    {
        return 'Milhq\\QualificationTable';
    }

    protected function getForm(?object $data): FormInterface
    {
        return $this->createForm(QualificationType::class, $data);
    }

    protected function redirectAfterSave(mixed $entity, bool $isNew): Response
    {
        return $isNew
            ? $this->redirectToRoute($this->getRoute('edit'), ['identifier' => $entity->getId()])
            : $this->redirectToRoute($this->getRoute('list'));
    }

    #[Route('/{id}/new-tier', '_tier_create')]
    public function addTier(Request $request, Qualification $qualification): Response
    {
        $tier = new QualificationTier();
        $tier->parent = $qualification;

        return $this->handleTierForm($request, $tier, true);
    }

    #[Route('/tiers/{id}/edit', '_tier_edit')]
    public function editTier(Request $request, QualificationTier $tier): Response
    {
        return $this->handleTierForm($request, $tier, false);
    }

    #[Route('/tiers/{id}/delete', '_tier_delete')]
    public function deleteTier(QualificationTier $tier): Response
    {
        $qualification = $tier->parent;
        $this->qualificationTierRepository->remove($tier);

        $this->addFlash('success', 'milhq.admin.qualification.tier.removed');
        return $this->redirectToRoute('milhq_admin_qualification_edit', ['identifier' => $qualification->getId()]);
    }

    private function handleTierForm(Request $request, QualificationTier $tier, bool $isNew): Response
    {
        $form = $this->createForm(QualificationTierType::class, $tier);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->render('@Forumify/form/simple_form_page.html.twig', [
                'admin' => true,
                'cancelPath' => $this->generateUrl('milhq_admin_qualification_edit', [
                    'identifier' => $tier->parent->getId(),
                ]),
                'form' => $form->createView(),
                'title' => 'milhq.admin.qualification.tier.' . ($isNew ? 'create' : 'edit'),
                'titleArgs' => $isNew ? ['qualification' => $tier->parent->getName()] : ['tier' => $tier->name],
            ]);
        }

        /** @var QualificationTier $tier */
        $tier = $form->getData();
        $newImage = $form->get('newImage')->getData();
        if ($newImage instanceof UploadedFile) {
            $tier->image = $this->mediaService->saveToFilesystem($this->milhqAssetStorage, $newImage);
        }

        $this->qualificationTierRepository->save($tier);

        $this->addFlash('success', 'milhq.admin.qualification.tier.saved');
        return $this->redirectToRoute('milhq_admin_qualification_edit', ['identifier' => $tier->parent->getId()]);
    }
}
