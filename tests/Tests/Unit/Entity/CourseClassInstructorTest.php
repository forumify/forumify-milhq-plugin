<?php

declare(strict_types=1);

namespace PluginTests\Tests\Unit\Entity;

use Forumify\Milhq\Entity\CourseClassInstructor;
use PHPUnit\Framework\TestCase;

class CourseClassInstructorTest extends TestCase
{
    public function testSetSoldierAcceptsNull(): void
    {
        $instructor = new CourseClassInstructor();
        $instructor->setSoldier(null);

        self::assertNull($instructor->getSoldier());
    }
}
