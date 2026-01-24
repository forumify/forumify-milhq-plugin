<?php

declare(strict_types=1);

namespace Forumify\Milhq\Admin\Controller;

use DateTime;
use Forumify\Milhq\Admin\Form\RecordType;
use Forumify\Milhq\Admin\Service\RecordService;
use Forumify\Milhq\Exception\SoldierNotFoundException;
use Forumify\Milhq\Repository\SoldierRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class RecordFormController extends AbstractController
{
    public function __construct(
        private readonly RecordService $recordService,
        private readonly SoldierRepository $soldierRepository,
    ) {
    }

    #[Route('/soldiers/create-record/{type}', 'record_form')]
    public function __invoke(
        Request $request,
        string $type
    ): Response {
        $this->denyAccessUnlessGranted("forumify-milhq.admin.records.{$type}_records.create");

        $data = ['created_at' => new DateTime()];

        $soldierIds = $request->query->get('soldiers', '');
        $soldierIds = array_filter(explode(',', $soldierIds));
        if (!empty($soldierIds)) {
            $data['soldiers'] = $this->soldierRepository->findBy(['id' => $soldierIds]);
        }

        $form = $this->createForm(RecordType::class, $data, ['type' => $type]);
        $form->handleRequest($request);
        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->render('@ForumifyMilhqPlugin/admin/soldiers/record_form.html.twig', [
                'form' => $form->createView(),
                'type' => $type,
            ]);
        }

        $data = $form->getData();

        try {
            $this->recordService->createRecord($type, $data);
        } catch (SoldierNotFoundException) {
            $this->addFlash('error', 'milhq.admin.requires_milhq_account');
            return $this->redirectToRoute('milhq_admin_soldier_list');
        }

        $this->addFlash('success', 'milhq.admin.soldiers.record_form.created');
        return $this->redirectToRoute('milhq_admin_soldier_list');
    }
}
