<?php

declare(strict_types=1);

namespace Forumify\Milhq\Controller;

use Forumify\Milhq\Repository\RankGroupRepository;
use Forumify\Milhq\Repository\RankRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class RankController extends AbstractController
{
    #[Route('/ranks', 'ranks')]
    public function __invoke(
        RankRepository $rankRepository,
        RankGroupRepository $rankGroupRepository,
    ): Response {
        return $this->render('@ForumifyMilhqPlugin/frontend/rank/rank.html.twig', [
            'groups' => $rankGroupRepository->findBy([], ['position' => 'ASC']),
            'hasUngroupedRanks' => $rankRepository->count(['group' => null]) > 0,
        ]);
    }
}
