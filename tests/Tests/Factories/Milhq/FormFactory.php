<?php

declare(strict_types=1);

namespace PluginTests\Tests\Factories\Milhq;

use Forumify\Milhq\Entity\Form;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

class FormFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return Form::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'name' => self::faker()->sentence(3),
        ];
    }
}
