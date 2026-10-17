<?php

declare(strict_types=1);

namespace PluginTests\Tests\Factories\Milhq;

use Forumify\Milhq\Entity\Document;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Document>
 */
class DocumentFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return Document::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'name' => self::faker()->words(3, true),
            'content' => self::faker()->paragraph(),
        ];
    }
}
