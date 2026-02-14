<?php

declare(strict_types=1);

namespace Forumify\Milhq\Controller;

use Forumify\Core\Repository\SettingRepository;
use Forumify\Milhq\Repository\FormRepository;
use Forumify\Milhq\Service\SoldierService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

use function Symfony\Component\String\u;

class OperationsCenterController extends AbstractController
{
    public function __construct(
        private readonly SoldierService $soldierService,
        private readonly SettingRepository $settingRepository,
        private readonly FormRepository $formRepository,
    ) {
    }

    #[Route('/operations-center', 'operations_center')]
    public function __invoke(): Response
    {
        $soldier = $this->soldierService->getLoggedInSoldier();
        if ($soldier === null) {
            $this->addFlash('error', 'The operations center can only be visited by enlisted personnel.');
            return $this->redirectToRoute('milhq_enlist');
        }

        $announcement = $this->settingRepository->get('milhq.opcenter.announcement');
        if (trim(strip_tags($announcement ?? '', ['img'])) === '') {
            $announcement = null;
        }

        return $this->render('@ForumifyMilhqPlugin/frontend/operations_center/operations_center.html.twig', [
            'announcement' => $announcement,
            'forms' => $this->formRepository->findAllSubmissionsAllowed(),
            'soldier' => $soldier,
            'milsimUnitsId' => $this->getMilsimUnitsId(),
        ]);
    }

    private function getMilsimUnitsId(): ?string
    {
        $url = $this->settingRepository->get('milhq.opcenter.milsimunits_link');
        if (empty($url)) {
            return null;
        }

        $path = parse_url($url, PHP_URL_PATH);
        if (!is_string($path) || empty($path)) {
            return null;
        }

        return u($path)->afterLast('/')->trimEnd('/')->toString();
    }
}
