<?php

declare(strict_types=1);

namespace Forumify\Milhq\Controller;

use Forumify\Core\Entity\User;
use Forumify\Core\Repository\SettingRepository;
use Forumify\Milhq\Form\EnlistmentType;
use Forumify\Milhq\Entity\Soldier;
use Forumify\Milhq\Service\EnlistService;
use Forumify\Milhq\Service\SoldierService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class EnlistController extends AbstractController
{
    public function __construct(
        private readonly EnlistService $enlistService,
        private readonly SoldierService $soldierService,
        private readonly SettingRepository $settingRepository,
    ) {
    }

    #[Route('/enlist', 'enlist')]
    public function __invoke(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var User $user */
        $user = $this->getUser();
        if ($user->isBanned()) {
            throw $this->createAccessDeniedException();
        }

        if (!$user->isEmailVerified()) {
            $this->addFlash('error', 'milhq.enlistment.not_verified');
            return $this->redirectToRoute('forumify_core_index');
        }

        if (!$this->enlistService->canEnlist()) {
            $this->addFlash('error', 'milhq.enlistment.not_eligible');
            return $this->redirectToRoute('forumify_core_index');
        }

        $enlistmentForm = $this->enlistService->getEnlistmentForm();
        if ($enlistmentForm === null) {
            $this->addFlash('error', 'milhq.enlistment.not_enabled');
            return $this->redirectToRoute('forumify_core_index');
        }

        /** @var Soldier|null $soldier */
        $soldier = $this->soldierService->getSoldier($user);
        if ($soldier !== null && !$request->query->get('force_new')) {
            return $this->render('@ForumifyMilhqPlugin/frontend/enlistment/enlist_success.html.twig', [
                'enlistmentTopic' => $soldier->getEnlistmentTopic(),
                'successMessage' => $enlistmentForm->getSuccessMessage(),
            ]);
        }

        $form = $this->createForm(EnlistmentType::class, null, [
            'form' => $enlistmentForm,
            'roleplay_names' => $this->settingRepository->get('milhq.enlistment.roleplay_names') ?? true,
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $soldier = $this->enlistService->enlist($form->getData());

            return $this->render('@ForumifyMilhqPlugin/frontend/enlistment/enlist_success.html.twig', [
                'enlistmentTopic' => $soldier->getEnlistmentTopic(),
                'successMessage' => $enlistmentForm->getSuccessMessage(),
            ]);
        }

        return $this->render('@ForumifyMilhqPlugin/frontend/enlistment/enlist.html.twig', [
            'form' => $form->createView(),
            'instructions' => $enlistmentForm->getInstructions(),
        ]);
    }
}
