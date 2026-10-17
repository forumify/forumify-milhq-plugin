<?php

declare(strict_types=1);

namespace PluginTests\Tests\Factories\Milhq;

use Forumify\Milhq\Entity\FormSubmission;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<FormSubmission>
 */
class FormSubmissionFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return FormSubmission::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'form' => FormFactory::new(),
            'soldier' => SoldierFactory::new(),
            'data' => [],
        ];
    }
}
