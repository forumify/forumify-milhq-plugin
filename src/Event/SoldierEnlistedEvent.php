<?php

declare(strict_types=1);

namespace Forumify\Milhq\Event;

use Forumify\Milhq\Entity\FormSubmission;
use Forumify\Milhq\Entity\Soldier;
use Symfony\Contracts\EventDispatcher\Event;

class SoldierEnlistedEvent extends Event
{
    public function __construct(
        public readonly Soldier $soldier,
        public readonly FormSubmission $submission,
    ) {
    }
}
