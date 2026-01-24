<?php

declare(strict_types=1);

namespace Forumify\Milhq\Repository;

use Forumify\Core\Repository\AbstractRepository;
use Forumify\Milhq\Entity\MissionRsvp;

/**
 * @extends AbstractRepository<MissionRsvp>
 */
class MissionRSVPRepository extends AbstractRepository
{
    public static function getEntityClass(): string
    {
        return MissionRsvp::class;
    }
}
