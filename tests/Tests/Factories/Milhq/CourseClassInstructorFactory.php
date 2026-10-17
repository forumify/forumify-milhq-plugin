<?php

declare(strict_types=1);

namespace PluginTests\Tests\Factories\Milhq;

use Forumify\Milhq\Entity\CourseClassInstructor;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<CourseClassInstructor>
 */
class CourseClassInstructorFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return CourseClassInstructor::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'class' => CourseClassFactory::new(),
            'soldier' => SoldierFactory::new(),
        ];
    }
}
