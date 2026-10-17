<?php

declare(strict_types=1);

namespace PluginTests\Tests\Application\Api;

use Forumify\Milhq\Entity\Enum\CourseResult;
use Forumify\Milhq\Repository\QualificationRecordRepository;
use Forumify\Milhq\Repository\ServiceRecordRepository;
use Forumify\Testing\Api\ApiTestCase;
use Forumify\Testing\Api\Crud\DeleteTestTrait;
use Forumify\Testing\Api\Crud\GetCollectionTestTrait;
use Forumify\Testing\Api\Crud\GetTestTrait;
use Forumify\Testing\Api\Crud\PatchTestTrait;
use Forumify\Testing\Factories\OAuth\OAuthClientFactory;
use PluginTests\Tests\Factories\Milhq\CourseClassFactory;
use PluginTests\Tests\Factories\Milhq\CourseClassInstructorFactory;
use PluginTests\Tests\Factories\Milhq\CourseClassStudentFactory;
use PluginTests\Tests\Factories\Milhq\CourseFactory;
use PluginTests\Tests\Factories\Milhq\CourseInstructorFactory;
use PluginTests\Tests\Factories\Milhq\QualificationFactory;
use PluginTests\Tests\Factories\Milhq\RankFactory;
use PluginTests\Tests\Factories\Milhq\SoldierFactory;
use Symfony\Component\HttpClient\Exception\ClientException;

class CourseClassApiTest extends ApiTestCase
{
    use GetCollectionTestTrait;
    use GetTestTrait;
    use PatchTestTrait;
    use DeleteTestTrait;

    protected static function factory(): string
    {
        return CourseClassFactory::class;
    }

    protected static function endpoint(): string
    {
        return '/api/milhq/course-classes';
    }

    protected function getPatchBody(): array
    {
        return ['title' => 'CLS Class #2'];
    }

    public function testGetClassesOfCourse(): void
    {
        $course = CourseFactory::createOne();
        $class = CourseClassFactory::createOne(['course' => $course]);
        CourseClassFactory::createOne();

        $classes = $this->getCollection("/api/milhq/courses/{$course->getId()}/classes");

        self::assertSame([$class->getId()], array_column($classes, 'id'));
    }

    public function testPostClassOnCourse(): void
    {
        $course = CourseFactory::createOne(['title' => 'Combat Life Saver']);

        $class = $this->post("/api/milhq/courses/{$course->getId()}/classes", [
            'json' => [
                'title' => 'CLS Class #1',
                'signupFrom' => '2026-11-01T18:00:00+00:00',
                'signupUntil' => '2026-11-07T18:00:00+00:00',
                'start' => '2026-11-08T18:00:00+00:00',
                'end' => '2026-11-08T20:00:00+00:00',
                'studentSlots' => 6,
            ],
        ]);

        self::assertSame(6, $class['studentSlots']);

        $class = $this->get($class['@id']);
        self::assertSame('Combat Life Saver', $class['course']['title']);
        self::assertSame(6, $class['studentSlots']);
        self::assertFalse($class['result']);
    }

    public function testPostClassRequiresDates(): void
    {
        $course = CourseFactory::createOne();

        $response = $this->request('POST', "/api/milhq/courses/{$course->getId()}/classes", [
            'json' => ['title' => 'CLS Class #1'],
        ]);

        self::assertSame(422, $response->getStatusCode());
    }

    public function testGetEmbedsClassRoster(): void
    {
        $rank = RankFactory::createOne(['name' => 'Private First Class', 'abbreviation' => 'PFC']);
        $class = CourseClassFactory::createOne();
        $slot = CourseInstructorFactory::createOne(['course' => $class->getCourse(), 'title' => 'Lead Instructor']);
        CourseClassInstructorFactory::createOne([
            'class' => $class,
            'soldier' => SoldierFactory::createOne(['name' => 'Jane Doe']),
            'instructor' => $slot,
        ]);
        CourseClassStudentFactory::createOne([
            'class' => $class,
            'soldier' => SoldierFactory::createOne(['name' => 'John Doe', 'rank' => $rank]),
            'notes' => 'Needs more practice',
        ]);

        $response = $this->get(self::endpoint() . "/{$class->getId()}");

        $instructor = $response['instructors'][0];
        self::assertSame('Jane Doe', $instructor['soldier']['name']);
        self::assertSame('Lead Instructor', $instructor['instructor']['title']);

        $student = $response['students'][0];
        self::assertSame('John Doe', $student['soldier']['name']);
        self::assertSame('PFC', $student['soldier']['rank']['abbreviation']);
        self::assertSame('Needs more practice', $student['notes']);
    }

    public function testManageStudentsAndReportResults(): void
    {
        $qualification = QualificationFactory::createOne(['name' => 'Combat Life Saver']);
        $course = CourseFactory::createOne(['qualifications' => [$qualification->getId()]]);
        $class = CourseClassFactory::createOne(['course' => $course, 'title' => 'CLS Class #1']);
        $soldier = SoldierFactory::createOne();

        $student = $this->post("/api/milhq/course-classes/{$class->getId()}/students", [
            'json' => ['soldier' => "/api/milhq/soldiers/{$soldier->getId()}"],
        ]);
        self::assertSame($soldier->getId(), $student['soldier']['id']);

        $this->patch("/api/milhq/course-class-students/{$student['id']}", [
            'json' => [
                'result' => CourseResult::Passed->value,
                'qualifications' => [$qualification->getId() => null],
            ],
        ]);

        $this->patch(self::endpoint() . "/{$class->getId()}", ['json' => ['result' => true]]);

        $serviceRecords = self::getContainer()->get(ServiceRecordRepository::class)->findBy(['soldier' => $soldier->getId()]);
        self::assertCount(1, $serviceRecords);
        self::assertSame('Graduated CLS Class #1', $serviceRecords[0]->getText());

        $qualificationRecords = self::getContainer()->get(QualificationRecordRepository::class)->findBy(['soldier' => $soldier->getId()]);
        self::assertCount(1, $qualificationRecords);

        $response = $this->request('PATCH', self::endpoint() . "/{$class->getId()}", [
            'json' => ['result' => false],
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
        ]);
        self::assertSame(422, $response->getStatusCode());
    }

    public function testStudentCanOnlySignUpOnce(): void
    {
        $student = CourseClassStudentFactory::createOne();

        $response = $this->request('POST', "/api/milhq/course-classes/{$student->getClass()->getId()}/students", [
            'json' => ['soldier' => "/api/milhq/soldiers/{$student->getSoldier()->getId()}"],
        ]);

        self::assertSame(422, $response->getStatusCode());
    }

    public function testClassesWithoutAccessAreHidden(): void
    {
        $this->oauthClient = OAuthClientFactory::createOne();
        $class = CourseClassFactory::createOne();

        self::assertEmpty($this->getCollection("/api/milhq/courses/{$class->getCourse()->getId()}/classes"));

        $this->expectException(ClientException::class);
        $this->expectExceptionCode(403);
        $this->post("/api/milhq/course-classes/{$class->getId()}/students", [
            'json' => ['soldier' => '/api/milhq/soldiers/' . SoldierFactory::createOne()->getId()],
        ]);
    }
}
