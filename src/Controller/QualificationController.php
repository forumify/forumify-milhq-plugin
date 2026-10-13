<?php

declare(strict_types=1);

namespace Forumify\Milhq\Controller;

use Forumify\Milhq\Repository\QualificationGroupRepository;
use Forumify\Milhq\Repository\QualificationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class QualificationController extends AbstractController
{
    #[Route('/qualifications', 'qualifications')]
    public function __invoke(
        QualificationRepository $qualificationRepository,
        QualificationGroupRepository $qualificationGroupRepository,
    ): Response {
        return $this->render('@ForumifyMilhqPlugin/frontend/qualification/qualification.html.twig', [
            'groups' => $qualificationGroupRepository->findBy([], ['position' => 'ASC']),
            'hasUngroupedQualifications' => $qualificationRepository->count(['group' => null]) > 0,
        ]);
    }
}
