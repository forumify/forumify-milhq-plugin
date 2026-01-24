<?php

declare(strict_types=1);

namespace Forumify\Milhq\Controller;

use Forumify\Core\Security\VoterAttribute;
use Forumify\Milhq\Entity\Operation;
use Forumify\Plugin\Attribute\PluginVersion;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[PluginVersion('forumify/forumify-milhq-plugin', 'premium')]
#[Route('/operations', 'operations_')]
class OperationController extends AbstractController
{
    #[Route('/', 'list')]
    public function list(): Response
    {
        return $this->render('@ForumifyMilhqPlugin/frontend/operation/list.html.twig');
    }

    #[Route('/{slug:operation}', 'view')]
    public function view(Operation $operation): Response
    {
        $this->denyAccessUnlessGranted(VoterAttribute::ACL->value, [
            'entity' => $operation,
            'permission' => 'view',
        ]);

        return $this->render('@ForumifyMilhqPlugin/frontend/operation/operation.html.twig', [
            'operation' => $operation,
        ]);
    }
}
