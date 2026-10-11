<?php

declare(strict_types=1);

namespace PluginTests\Tests\Factories\Milhq\Record;

use Forumify\Milhq\Entity\Record\AssignmentRecord;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

class AssignmentRecordFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return AssignmentRecord::class;
    }

    protected function defaults(): array|callable
    {
        return [];
    }
}
