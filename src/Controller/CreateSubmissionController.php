<?php

declare(strict_types=1);

namespace Forumify\Milhq\Controller;

use Forumify\Milhq\Form\SubmissionFormType;
use Forumify\Milhq\Entity\Form;
use Forumify\Milhq\Entity\FormSubmission;
use Forumify\Milhq\Repository\FormSubmissionRepository;
use Forumify\Milhq\Service\SoldierService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CreateSubmissionController extends AbstractController
{
    public function __construct(
        private readonly SoldierService $soldierService,
        private readonly FormSubmissionRepository $formSubmissionRepository,
    ) {
    }

    #[Route('/form/{id}/create-submission', 'form_submission_create')]
    public function __invoke(Form $submissionForm, Request $request): Response
    {
        $soldier = $this->soldierService->getLoggedInSoldier();
        if ($soldier === null) {
            throw $this->createAccessDeniedException('You need to enlist before you can create form submissions');
        }

        $form = $this->createForm(SubmissionFormType::class, null, ['submissionForm' => $submissionForm]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $submission = new FormSubmission();
            $submission->setForm($submissionForm);
            $submission->setSoldier($soldier);
            if ($submissionForm->getDefaultStatus()) {
                $submission->setStatus($submissionForm->getDefaultStatus());
            }
            $submission->setData($form->getData());

            $this->formSubmissionRepository->save($submission);

            $this->addFlash('success', 'milhq.opcenter.submission_created');
            return $this->redirectToRoute('milhq_operations_center');
        }

        return $this->render('@ForumifyMilhqPlugin/frontend/form/create_submission.html.twig', [
            'form' => $form->createView(),
            'submissionForm' => $submissionForm,
        ]);
    }
}
