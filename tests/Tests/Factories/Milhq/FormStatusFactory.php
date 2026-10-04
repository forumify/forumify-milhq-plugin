<?php

declare(strict_types=1);

namespace PluginTests\Tests\Factories\Milhq;

use Forumify\Milhq\Entity\FormStatus;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

class FormStatusFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return FormStatus::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'color' => self::faker()->hexColor(),
        ];
    }
}
