<?php

declare(strict_types=1);

namespace PluginTests\Tests\Factories\Milhq;

use Forumify\Milhq\Entity\CourseClassStudent;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<CourseClassStudent>
 */
class CourseClassStudentFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return CourseClassStudent::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'class' => CourseClassFactory::new(),
            'soldier' => SoldierFactory::new(),
        ];
    }
}
