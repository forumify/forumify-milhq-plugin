<?php

declare(strict_types=1);

namespace Forumify\Milhq\Event;

use Forumify\Milhq\Entity\Record\RecordInterface;
use Symfony\Contracts\EventDispatcher\Event;

class RecordsCreatedEvent extends Event
{
    /**
     * @param array<RecordInterface> $records
     */
    public function __construct(
        public readonly array $records,
        public readonly bool $sendNotification,
    ) {
    }
}
