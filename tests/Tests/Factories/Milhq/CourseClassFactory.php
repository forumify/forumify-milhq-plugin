<?php

declare(strict_types=1);

namespace PluginTests\Tests\Factories\Milhq;

use DateTime;
use Forumify\Milhq\Entity\CourseClass;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<CourseClass>
 */
class CourseClassFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return CourseClass::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'course' => CourseFactory::new(),
            'title' => self::faker()->sentence(3),
            'description' => self::faker()->paragraph(),
            'signupFrom' => new DateTime('-1 day'),
            'signupUntil' => new DateTime('+1 day'),
            'start' => new DateTime('+2 days'),
            'end' => new DateTime('+2 days 2 hours'),
        ];
    }
}
