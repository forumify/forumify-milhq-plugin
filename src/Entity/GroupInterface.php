<?php

declare(strict_types=1);

namespace Forumify\Milhq\Entity;

use Forumify\Core\Entity\AuditableEntityInterface;
use Forumify\Core\Entity\SortableEntityInterface;

interface GroupInterface extends SortableEntityInterface, AuditableEntityInterface
{
    public function getId(): int;

    public function getTitle(): string;

    public function setTitle(string $title): void;
}
