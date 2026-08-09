<?php

declare(strict_types=1);

namespace PluginTests\Tests\Unit\Entity;

use Forumify\Milhq\Entity\CourseClassStudent;
use PHPUnit\Framework\TestCase;

class CourseClassStudentTest extends TestCase
{
    public function testSetSoldierAcceptsNull(): void
    {
        $student = new CourseClassStudent();
        $student->setSoldier(null);

        self::assertNull($student->getSoldier());
    }
}
