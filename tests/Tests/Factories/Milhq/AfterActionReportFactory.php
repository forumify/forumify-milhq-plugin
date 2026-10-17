<?php

declare(strict_types=1);

namespace PluginTests\Tests\Factories\Milhq;

use Forumify\Milhq\Entity\AfterActionReport;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<AfterActionReport>
 */
class AfterActionReportFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return AfterActionReport::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'mission' => MissionFactory::new(),
            'unit' => UnitFactory::new(),
        ];
    }
}
