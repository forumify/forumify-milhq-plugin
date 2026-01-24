<?php

declare(strict_types=1);

namespace Forumify\Milhq\Admin\Form;

use Forumify\Milhq\Entity\Soldier;
use Forumify\Milhq\Entity\Position;
use Forumify\Milhq\Entity\Rank;
use Forumify\Milhq\Entity\Status;
use Forumify\Milhq\Entity\Unit;

class Discharge
{
    public string $type;
    public ?string $reason = null;
    public ?Rank $rank = null;
    public ?Unit $unit = null;
    public ?Position $position = null;
    public ?Status $status = null;

    public function __construct(
        public readonly Soldier $user,
    ) {
        $this->status = $user->getStatus();
        $this->rank = $user->getRank();
        $this->unit = $user->getUnit();
        $this->position = $user->getPosition();
    }
}
