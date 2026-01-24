<?php

declare(strict_types=1);

namespace Forumify\Milhq\Components;

use Forumify\Milhq\Entity\Record\ServiceRecord;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

#[AsLiveComponent('ServiceRecordTable', '@ForumifyMilhqPlugin/frontend/components/record_table.html.twig')]
class ServiceRecordTable extends AbstractRecordTable
{
    protected function getEntityClass(): string
    {
        return ServiceRecord::class;
    }
}
