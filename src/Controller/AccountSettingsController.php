<?php

declare(strict_types=1);

namespace Forumify\Milhq\Controller;

use Forumify\Core\Controller\AccountSettingsController as ForumifyAccountSettingsController;
use Forumify\Core\Entity\User;
use Forumify\Milhq\Service\SyncSoldierService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\AutowireDecorated;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsDecorator(ForumifyAccountSettingsController::class)]
class AccountSettingsController extends AbstractController
{
    public function __construct(
        #[AutowireDecorated]
        private readonly ForumifyAccountSettingsController $inner,
        private readonly SyncSoldierService $syncUserService,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $response = ($this->inner)($request);

        if ($response instanceof RedirectResponse) {
            /** @var User $user */
            $user = $this->getUser();
            $this->syncUserService->sync($user->getId(), false);
        }

        return $response;
    }
}
