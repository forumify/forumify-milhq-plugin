<?php

declare(strict_types=1);

namespace PluginTests\Tests\Factories\Milhq;

use Forumify\Milhq\Entity\FormField;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

class FormFieldFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return FormField::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'required' => false,
            'type' => 'text',
        ];
    }
}
