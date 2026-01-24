<?php

declare(strict_types=1);

namespace Forumify\Milhq\Repository;

use Forumify\Core\Repository\AbstractRepository;
use Forumify\Milhq\Entity\FormSubmission;

/**
 * @extends AbstractRepository<FormSubmission>
 */
class FormSubmissionRepository extends AbstractRepository
{
    public static function getEntityClass(): string
    {
        return FormSubmission::class;
    }
}
