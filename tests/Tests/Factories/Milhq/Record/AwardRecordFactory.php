<?php

declare(strict_types=1);

namespace PluginTests\Tests\Factories\Milhq\Record;

use Forumify\Milhq\Entity\Record\AwardRecord;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

class AwardRecordFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return AwardRecord::class;
    }

    protected function defaults(): array|callable
    {
        return [];
    }
}
