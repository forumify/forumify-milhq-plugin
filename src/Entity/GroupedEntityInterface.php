<?php

declare(strict_types=1);

namespace Forumify\Milhq\Entity;

use Forumify\Core\Entity\SortableEntityInterface;

interface GroupedEntityInterface extends SortableEntityInterface
{
    public function getGroup(): ?GroupInterface;
}
