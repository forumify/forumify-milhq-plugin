<?php

declare(strict_types=1);

namespace Forumify\Milhq\Admin\Controller;

use Forumify\Milhq\Admin\Form\SubmissionStatusType;
use Forumify\Milhq\Admin\Service\SubmissionStatusUpdateService;
use Forumify\Milhq\Entity\FormSubmission;
use Forumify\Milhq\Repository\FormRepository;
use Forumify\Milhq\Repository\FormSubmissionRepository;
use Forumify\Core\Security\VoterAttribute;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/submissions', 'submission_')]
#[IsGranted('milhq.admin.submissions.view')]
class SubmissionController extends AbstractController
{
    public function __construct(
        private readonly FormRepository $formRepository,
        private readonly FormSubmissionRepository $formSubmissionRepository,
    ) {
    }

    #[Route('', 'list')]
    public function list(Request $request): Response
    {
        $formId = $request->query->get('form');
        $form = $formId !== null ? $this->formRepository->find($formId) : null;

        return $this->render('@ForumifyMilhqPlugin/admin/submissions/list/list.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}', 'view')]
    public function view(
        SubmissionStatusUpdateService $submissionStatusUpdateService,
        FormSubmission $submission,
        Request $request
    ): Response {
        $this->denyAccessUnlessGranted(VoterAttribute::ACL->value, [
            'permission' => 'view_submissions',
            'entity' => $submission->getForm(),
        ]);

        $canManage = $this->isGranted('milhq.admin.submissions.assign_statuses')
            && $this->isGranted(VoterAttribute::ACL->value, [
                'permission' => 'manage_submissions',
                'entity' => $submission->getForm(),
            ]);

        $form = null;
        if ($canManage) {
            $form = $this->createForm(SubmissionStatusType::class);
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $statusRecord = $form->getData();
                $submissionStatusUpdateService->createStatusRecord($submission, $statusRecord);

                $this->addFlash('success', 'milhq.admin.submissions.view.status_created');
                return $this->redirectToRoute('milhq_admin_submission_view', ['id' => $submission->getId()]);
            }
        }

        return $this->render('@ForumifyMilhqPlugin/admin/submissions/view/form.html.twig', [
            'form' => $form?->createView(),
            'submission' => $submission,
        ]);
    }

    #[Route('/{id}/delete', 'delete')]
    public function delete(Request $request, FormSubmission $formSubmission): Response
    {
        if (!$this->isGranted('milhq.admin.submissions.delete')) {
            $this->addFlash('error', 'You are not allowed to delete submissions.');
            return $this->redirectToRoute('milhq_admin_submission_list');
        }

        $this->denyAccessUnlessGranted(VoterAttribute::ACL->value, [
            'permission' => 'manage_submissions',
            'entity' => $formSubmission->getForm(),
        ]);

        if (!$request->query->get('confirmed')) {
            return $this->render('@ForumifyMilhqPlugin/admin/submissions/delete/delete.html.twig', [
                'submission' => $formSubmission,
            ]);
        }

        $this->formSubmissionRepository->remove($formSubmission);

        $this->addFlash('success', 'Submission deleted.');
        return $this->redirectToRoute('milhq_admin_submission_list');
    }
}
