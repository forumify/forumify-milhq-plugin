<?php

declare(strict_types=1);

namespace Forumify\Milhq\Controller;

use Forumify\Milhq\Repository\AwardGroupRepository;
use Forumify\Milhq\Repository\AwardRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AwardController extends AbstractController
{
    #[Route('/awards', 'awards')]
    public function __invoke(
        AwardRepository $awardRepository,
        AwardGroupRepository $awardGroupRepository,
    ): Response {
        return $this->render('@ForumifyMilhqPlugin/frontend/award/award.html.twig', [
            'groups' => $awardGroupRepository->findBy([], ['position' => 'ASC']),
            'hasUngroupedAwards' => $awardRepository->count(['group' => null]) > 0,
        ]);
    }
}
