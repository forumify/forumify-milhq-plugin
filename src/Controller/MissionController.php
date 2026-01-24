<?php

declare(strict_types=1);

namespace Forumify\Milhq\Controller;

use Forumify\Core\Security\VoterAttribute;
use Forumify\Milhq\Form\MissionType;
use Forumify\Milhq\Entity\Mission;
use Forumify\Milhq\Repository\MissionRepository;
use Forumify\Milhq\Repository\OperationRepository;
use Forumify\Plugin\Attribute\PluginVersion;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[PluginVersion('forumify/forumify-milhq-plugin', 'premium')]
#[Route('/missions', 'missions_')]
class MissionController extends AbstractController
{
    public function __construct(
        private readonly OperationRepository $operationRepository,
        private readonly MissionRepository $missionRepository,
    ) {
    }

    #[Route('/{id<\d+>}', 'view')]
    public function view(Mission $mission): Response
    {
        $this->denyAccessUnlessGranted(VoterAttribute::ACL->value, [
            'entity' => $mission->getOperation(),
            'permission' => 'view_missions',
        ]);

        return $this->render('@ForumifyMilhqPlugin/frontend/mission/mission.html.twig', [
            'mission' => $mission,
        ]);
    }

    #[Route('/new', 'create')]
    public function create(Request $request): Response
    {
        $operation = $this->operationRepository->find($request->query->get('operation'));
        if ($operation === null) {
            throw $this->createNotFoundException();
        }

        $this->denyAccessUnlessGranted(VoterAttribute::ACL->value, [
            'entity' => $operation,
            'permission' => 'manage_missions',
        ]);

        $mission = new Mission();
        $mission->setOperation($operation);
        $mission->setBriefing($operation->getMissionBriefingTemplate());

        return $this->handleMissionForm($mission, true, $request);
    }

    #[Route('/{id<\d+>}/edit', 'edit')]
    public function edit(Mission $mission, Request $request): Response
    {
        $operation = $mission->getOperation();
        $this->denyAccessUnlessGranted(VoterAttribute::ACL->value, [
            'entity' => $operation,
            'permission' => 'manage_missions',
        ]);

        return $this->handleMissionForm($mission, false, $request);
    }

    #[Route('/{id<\d+>}/delete', 'delete')]
    public function delete(Mission $mission, Request $request): Response
    {
        $operation = $mission->getOperation();
        $this->denyAccessUnlessGranted(VoterAttribute::ACL->value, [
            'entity' => $operation,
            'permission' => 'manage_missions',
        ]);

        if (!$request->query->get('confirmed')) {
            return $this->render('@ForumifyMilhqPlugin/frontend/mission/delete.html.twig', [
                'mission' => $mission,
            ]);
        }

        $operationSlug = $mission->getOperation()->getSlug();
        $this->missionRepository->remove($mission);

        $this->addFlash('success', 'milhq.mission.deleted');
        return $this->redirectToRoute('milhq_operations_view', ['slug' => $operationSlug]);
    }

    private function handleMissionForm(Mission $mission, bool $isNew, Request $request): Response
    {
        $form = $this->createForm(MissionType::class, $mission);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $mission = $form->getData();
            $this->missionRepository->save($mission);

            $this->addFlash('success', $isNew ? 'milhq.mission.created' : 'milhq.mission.edited');
            return $this->redirectToRoute('milhq_missions_view', [
                'id' => $mission->getId(),
            ]);
        }

        return $this->render('@Forumify/form/simple_form_page.html.twig', [
            'cancelPath' => $isNew
                ? $this->generateUrl('milhq_operations_view', [
                    'slug' => $mission->getOperation()->getSlug(),
                ])
                : $this->generateUrl('milhq_missions_view', ['id' => $mission->getId()]),
            'form' => $form->createView(),
            'title' => $isNew ? 'milhq.mission.create' : 'milhq.mission.edit',
        ]);
    }
}
