<?php

declare(strict_types=1);

namespace PluginTests\Tests\Factories\Milhq;

use Forumify\Milhq\Entity\CourseInstructor;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<CourseInstructor>
 */
class CourseInstructorFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return CourseInstructor::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'course' => CourseFactory::new(),
            'title' => self::faker()->jobTitle(),
        ];
    }
}
