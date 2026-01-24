<?php

declare(strict_types=1);

namespace Forumify\Milhq\Repository;

use Forumify\Core\Repository\AbstractRepository;
use Forumify\Milhq\Entity\Document;

class DocumentRepository extends AbstractRepository
{
    public static function getEntityClass(): string
    {
        return Document::class;
    }
}
