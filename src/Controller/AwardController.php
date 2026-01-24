<?php

declare(strict_types=1);

namespace Forumify\Milhq\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AwardController extends AbstractController
{
    #[Route('/awards', 'awards')]
    public function __invoke(): Response
    {
        return $this->render('@ForumifyMilhqPlugin/frontend/award/award.html.twig');
    }
}
