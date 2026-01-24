<?php

declare(strict_types=1);

namespace Forumify\Milhq\Components;

use Forumify\Milhq\Entity\Record\CombatRecord;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

#[AsLiveComponent('CombatRecordTable', '@ForumifyMilhqPlugin/frontend/components/record_table.html.twig')]
class CombatRecordTable extends AbstractRecordTable
{
    protected function getEntityClass(): string
    {
        return CombatRecord::class;
    }
}
