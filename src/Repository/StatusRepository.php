<?php

declare(strict_types=1);

namespace Forumify\Milhq\Repository;

use Forumify\Core\Repository\AbstractRepository;
use Forumify\Milhq\Entity\Status;

/**
 * @extends AbstractRepository<Status>
 */
class StatusRepository extends AbstractRepository
{
    public static function getEntityClass(): string
    {
        return Status::class;
    }
}
