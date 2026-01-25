<?php

declare(strict_types=1);

namespace PluginTests\Tests\Factories\Milhq\Record;

use Forumify\Milhq\Entity\Record\QualificationRecord;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

class QualificationRecordFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return QualificationRecord::class;
    }

    protected function defaults(): array|callable
    {
        return [];
    }
}
