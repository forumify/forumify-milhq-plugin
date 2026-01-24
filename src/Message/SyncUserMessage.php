<?php

declare(strict_types=1);

namespace Forumify\Milhq\Message;

use Forumify\Core\Messenger\AsyncMessageInterface;

class SyncSoldierMessage implements AsyncMessageInterface
{
    public function __construct(
        public ?int $userId = null,
    ) {
    }
}
