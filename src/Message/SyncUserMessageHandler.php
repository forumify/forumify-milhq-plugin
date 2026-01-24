<?php

declare(strict_types=1);

namespace Forumify\Milhq\Message;

use Forumify\Milhq\Service\SyncSoldierService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(handles: SyncSoldierMessage::class)]
class SyncSoldierMessageHandler
{
    public function __construct(
        private readonly SyncSoldierService $syncUserService,
    ) {
    }

    public function __invoke(SyncSoldierMessage $message): void
    {
        $this->syncUserService->doSync($message);
    }
}
