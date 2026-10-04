<?php

declare(strict_types=1);

namespace Forumify\Milhq\Repository;

use Forumify\Core\Repository\AbstractRepository;
use Forumify\Milhq\Entity\FormStatus;

/**
 * @extends AbstractRepository<FormStatus>
 */
class FormStatusRepository extends AbstractRepository
{
    public static function getEntityClass(): string
    {
        return FormStatus::class;
    }
}
