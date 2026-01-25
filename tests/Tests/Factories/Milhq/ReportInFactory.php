<?php

declare(strict_types=1);

namespace PluginTests\Tests\Factories\Milhq;

use Forumify\Milhq\Entity\ReportIn;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<ReportIn>
 */
class ReportInFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return ReportIn::class;
    }

    protected function defaults(): array|callable
    {
        return [];
    }
}
