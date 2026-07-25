<?php

declare(strict_types=1);

namespace Forumify\Milhq\Admin\Controller;

use Forumify\Admin\Crud\AbstractCrudController;
use Forumify\Core\Service\MediaService;
use Forumify\Milhq\Admin\Form\AwardTierType;
use Forumify\Milhq\Admin\Form\AwardToTierType;
use Forumify\Milhq\Admin\Form\AwardType;
use Forumify\Milhq\Admin\Service\AwardToTierService;
use Forumify\Milhq\Entity\Award;
use Forumify\Milhq\Entity\AwardTier;
use Forumify\Milhq\Repository\AwardTierRepository;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @extends AbstractCrudController<Award>
 */
#[Route('/awards', 'award')]
#[IsGranted('milhq.admin.organization.awards.view')]
class AwardController extends AbstractCrudController
{
    protected ?string $permissionView = 'milhq.admin.organization.awards.view';
    protected ?string $permissionCreate = 'milhq.admin.organization.awards.create';
    protected ?string $permissionEdit = 'milhq.admin.organization.awards.manage';
    protected ?string $permissionDelete = 'milhq.admin.organization.awards.delete';

    protected string $formTemplate = '@ForumifyMilhqPlugin/admin/awards/form.html.twig';

    public function __construct(
        private readonly AwardTierRepository $awardTierRepository,
        private readonly MediaService $mediaService,
        private readonly FilesystemOperator $milhqAssetStorage,
        private readonly AwardToTierService $awardToTierService,
    ) {
    }

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

    protected function redirectAfterSave(mixed $entity, bool $isNew): Response
    {
        return $isNew
            ? $this->redirectToRoute($this->getRoute('edit'), ['identifier' => $entity->getId()])
            : $this->redirectToRoute($this->getRoute('list'));
    }

    #[Route('/{id}/new-tier', '_tier_create')]
    public function addTier(Request $request, Award $award): Response
    {
        $tier = new AwardTier();
        $tier->parent = $award;

        return $this->handleTierForm($request, $tier, true);
    }

    #[Route('/tiers/{id}/edit', '_tier_edit')]
    public function editTier(Request $request, AwardTier $tier): Response
    {
        return $this->handleTierForm($request, $tier, false);
    }

    #[Route('/tiers/{id}/delete', '_tier_delete')]
    public function deleteTier(AwardTier $tier): Response
    {
        $award = $tier->parent;
        $this->awardTierRepository->remove($tier);

        $this->addFlash('success', 'milhq.admin.award.tier.removed');
        return $this->redirectToRoute('milhq_admin_award_edit', ['identifier' => $award->getId()]);
    }

    private function handleTierForm(Request $request, AwardTier $tier, bool $isNew): Response
    {
        $form = $this->createForm(AwardTierType::class, $tier);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->render('@Forumify/form/simple_form_page.html.twig', [
                'admin' => true,
                'cancelPath' => $this->generateUrl('milhq_admin_award_edit', [
                    'identifier' => $tier->parent->getId(),
                ]),
                'form' => $form->createView(),
                'title' => 'milhq.admin.award.tier.' . ($isNew ? 'create' : 'edit'),
                'titleArgs' => $isNew ? ['award' => $tier->parent->getName()] : ['tier' => $tier->name],
            ]);
        }

        /** @var AwardTier $tier */
        $tier = $form->getData();
        $newImage = $form->get('newImage')->getData();
        if ($newImage instanceof UploadedFile) {
            $tier->image = $this->mediaService->saveToFilesystem($this->milhqAssetStorage, $newImage);
        }

        $this->awardTierRepository->save($tier);

        $this->addFlash('success', 'milhq.admin.award.tier.saved');
        return $this->redirectToRoute('milhq_admin_award_edit', ['identifier' => $tier->parent->getId()]);
    }

    #[Route('/{id}/make-tier', '_make_tier')]
    public function makeTier(Request $request, Award $award): Response
    {
        $form = $this->createForm(AwardToTierType::class, ['award' => $award->getName()]);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->render('@Forumify/form/simple_form_page.html.twig', [
                'admin' => true,
                'cancelPath' => $this->generateUrl('milhq_admin_award_list', [
                    'identifier' => $award->getId(),
                ]),
                'form' => $form->createView(),
                'title' => 'Migrate Award To Tier',
            ]);
        }

        $data = $form->getData();
        $this->awardToTierService->awardToTier(
            $award,
            $data['targetAward'],
            $data,
        );

        $this->addFlash('success', "{$award->getName()} was successfully turned into a tier.");
        return $this->redirectToRoute('milhq_admin_award_list');
    }
}
