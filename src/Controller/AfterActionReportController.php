<?php

declare(strict_types=1);

namespace Forumify\Milhq\Controller;

use Forumify\Core\Entity\User;
use Forumify\Core\Security\VoterAttribute;
use Forumify\Milhq\Form\AfterActionReportType;
use Forumify\Milhq\Entity\AfterActionReport;
use Forumify\Milhq\Entity\MissionRsvp;
use Forumify\Milhq\Entity\Soldier;
use Forumify\Milhq\Entity\Unit;
use Forumify\Milhq\Repository\AfterActionReportRepository;
use Forumify\Milhq\Repository\MissionRepository;
use Forumify\Milhq\Repository\MissionRSVPRepository;
use Forumify\Milhq\Repository\SoldierRepository;
use Forumify\Milhq\Service\AfterActionReportService;
use Forumify\Milhq\Service\SoldierService;
use Forumify\Plugin\Attribute\PluginVersion;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[PluginVersion('forumify/forumify-milhq-plugin', 'premium')]
#[Route('/aar', 'aar_')]
class AfterActionReportController extends AbstractController
{
    public function __construct(
        private readonly AfterActionReportRepository $afterActionReportRepository,
        private readonly MissionRepository $missionRepository,
        private readonly AfterActionReportService $afterActionReportService,
        private readonly SoldierService $userService,
        private readonly SoldierRepository $userRepository,
        private readonly Packages $packages,
    ) {
    }

    #[Route('/{id<\d+>}', 'view')]
    public function view(AfterActionReport $aar): Response
    {
        $this->denyAccessUnlessGranted(VoterAttribute::ACL->value, [
            'entity' => $aar->getMission()->getOperation(),
            'permission' => 'manage_after_action_reports',
        ]);

        $allUserIds = [];
        foreach ($aar->getAttendance() as $list) {
            foreach ($list as $userId) {
                $allUserIds[] = $userId;
            }
        }

        /** @var array<Soldier> $users */
        $users = $this->userRepository->findBy(['id' => $allUserIds]);
        $allUserIds = array_map(fn (Soldier $user) => $user->getId(), $users);
        $users = array_combine($allUserIds, $users);

        $attendance = $aar->getAttendance();
        foreach ($attendance as &$list) {
            foreach ($list as $k => $userId) {
                $list[$k] = $users[$userId] ?? null;
            }
        }
        unset($list);

        foreach ($attendance as &$list) {
            $list = array_filter($list);
            $this->userService->sortSoldiers($list);
        }
        unset($list);

        return $this->render('@ForumifyMilhqPlugin/frontend/aar/aar.html.twig', [
            'aar' => $aar,
            'attendance' => $attendance,
            'attendanceStates' => $this->afterActionReportService->getAttendanceStates(),
        ]);
    }

    #[Route('/new', 'create')]
    public function create(Request $request): Response
    {
        $mission = $this->missionRepository->find($request->query->get('mission'));
        if ($mission === null) {
            throw $this->createNotFoundException();
        }

        $this->denyAccessUnlessGranted(VoterAttribute::ACL->value, [
            'entity' => $mission->getOperation(),
            'permission' => 'manage_after_action_reports',
        ]);

        $aar = new AfterActionReport();
        $aar->setMission($mission);
        $aar->setReport($mission->getOperation()->getAfterActionReportTemplate());

        return $this->handleAfterActionReportForm($aar, true, $request);
    }

    #[Route('/{id<\d+>}/edit', 'edit')]
    public function edit(AfterActionReport $aar, Request $request): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();
        if ($aar->getCreatedBy()?->getId() !== $user?->getId()) {
            $this->denyAccessUnlessGranted(VoterAttribute::ACL->value, [
                'entity' => $aar->getMission()->getOperation(),
                'permission' => 'manage_after_action_reports',
            ]);
        }

        return $this->handleAfterActionReportForm($aar, false, $request);
    }

    #[Route('/{id<\d+>}/delete', 'delete')]
    public function delete(AfterActionReport $aar, Request $request): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();
        if ($aar->getCreatedBy()?->getId() !== $user?->getId()) {
            $this->denyAccessUnlessGranted(VoterAttribute::ACL->value, [
                'entity' => $aar->getMission()->getOperation(),
                'permission' => 'manage_after_action_reports',
            ]);
        }

        if (!$request->query->get('confirmed')) {
            return $this->render('@ForumifyMilhqPlugin/frontend/aar/delete.html.twig', [
                'aar' => $aar,
            ]);
        }

        $missionId = $aar->getMission()->getId();
        $this->afterActionReportRepository->remove($aar);

        $this->addFlash('success', 'milhq.aar.deleted');
        return $this->redirectToRoute('milhq_missions_view', ['id' => $missionId]);
    }

    private function handleAfterActionReportForm(AfterActionReport $aar, bool $isNew, Request $request): Response
    {
        $form = $this->createForm(AfterActionReportType::class, $aar, ['is_new' => $isNew]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var AfterActionReport $aar */
            $aar = $form->getData();
            $attendance = $form->get('attendanceJson')->getData();

            $this->afterActionReportService->createOrUpdate($aar, $attendance, $isNew);
            $this->addFlash('success', $isNew ? 'milhq.aar.created' : 'milhq.aar.edited');
            return $this->redirectToRoute('milhq_aar_view', ['id' => $aar->getId()]);
        }

        return $this->render('@ForumifyMilhqPlugin/frontend/aar/form.html.twig', [
            'aar' => $aar,
            'attendanceStatus' => $this->afterActionReportService->getAttendanceStates(),
            'cancelPath' => $isNew
                ? $this->generateUrl('milhq_missions_view', ['id' => $aar->getMission()->getId()])
                : $this->generateUrl('milhq_aar_view', ['id' => $aar->getId()]),
            'form' => $form->createView(),
            'title' => $isNew ? 'milhq.aar.create' : 'milhq.aar.edit',
        ]);
    }

    #[Route('/unit/{id}', 'unit')]
    public function getUnit(Unit $unit, Request $request, MissionRSVPRepository $missionRSVPRepository): JsonResponse
    {
        $users = $unit->getSoldiers()->toArray();
        $missionId = $request->query->get('mission');
        $rsvps = $missionId === null
            ? []
            : $missionRSVPRepository->findBy([
                'mission' => $missionId,
                'user' => $users,
            ]);

        $usersToRsvp = [];
        /** @var MissionRsvp $rsvp */
        foreach ($rsvps as $rsvp) {
            $usersToRsvp[$rsvp->getSoldier()->getId()] = $rsvp->isGoing();
        }

        $response = [];
        foreach ($users as $user) {
            $row = [
                'id' => $user->getId(),
                'name' => $user->getName(),
                'rankImage' => null,
                'rsvp' => $usersToRsvp[$user->getId()] ?? null,
            ];

            $rankImg = $user->getRank()?->getImage();
            if ($rankImg) {
                $row['rankImage'] = $this->packages->getUrl($rankImg, 'milhq.asset');
            }

            $response[] = $row;
        }

        return new JsonResponse($response);
    }
}
