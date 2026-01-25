<?php

declare(strict_types=1);

namespace Forumify\Milhq\Admin\Controller;

use Forumify\Milhq\Admin\Form\Discharge;
use Forumify\Milhq\Admin\Form\DischargeType;
use Forumify\Milhq\Entity\Soldier;
use Forumify\Milhq\Service\DischargeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class DischargeController extends AbstractController
{
    public function __construct(
        private readonly DischargeService $dischargeService,
    ) {
    }

    #[Route('/users/{id}/discharge', 'soldier_discharge')]
    #[IsGranted('milhq.admin.soldiers.discharge')]
    public function __invoke(Request $request, Soldier $soldier): Response
    {
        $discharge = new Discharge($soldier);
        $form = $this->createForm(DischargeType::class, $discharge);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $discharge = $form->getData();
            $this->dischargeService->discharge($discharge);

            $this->addFlash('success', 'Discharged user.');
            return $this->redirectToRoute('milhq_admin_soldier_list');
        }

        return $this->render('@ForumifyMilhqPlugin/admin/soldiers/edit/discharge.html.twig', [
            'form' => $form,
            'soldier' => $soldier,
        ]);
    }
}
