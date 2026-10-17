<?php

declare(strict_types=1);

namespace PluginTests\Tests\Application\Api;

use Forumify\Testing\Api\ApiTestCase;
use Forumify\Testing\Api\Crud\DeleteTestTrait;
use Forumify\Testing\Api\Crud\GetCollectionTestTrait;
use Forumify\Testing\Api\Crud\GetTestTrait;
use Forumify\Testing\Api\Crud\PatchTestTrait;
use Forumify\Testing\Api\Crud\PostTestTrait;
use Forumify\Testing\Factories\OAuth\OAuthClientFactory;
use PluginTests\Tests\Factories\Milhq\CourseFactory;
use PluginTests\Tests\Factories\Milhq\CourseInstructorFactory;
use PluginTests\Tests\Factories\Milhq\RankFactory;
use Symfony\Component\HttpClient\Exception\ClientException;

class CourseApiTest extends ApiTestCase
{
    use GetCollectionTestTrait;
    use GetTestTrait;
    use PostTestTrait;
    use PatchTestTrait;
    use DeleteTestTrait;

    protected static function factory(): string
    {
        return CourseFactory::class;
    }

    protected static function endpoint(): string
    {
        return '/api/milhq/courses';
    }

    protected function getPostBody(): array
    {
        return [
            [
                'title' => 'Combat Life Saver',
                'description' => 'Learn about being a CLS!',
                'minimumRank' => '/api/milhq/ranks/' . RankFactory::createOne(['name' => 'Private'])->getId(),
                'qualifications' => [1, 2],
            ],
            function (array $data): void {
                self::assertSame('Combat Life Saver', $data['title']);
                self::assertSame('Private', $data['minimumRank']['name']);
                self::assertSame([1, 2], $data['qualifications']);
            },
        ];
    }

    protected function getPatchBody(): array
    {
        return ['title' => 'Combat Life Saver Refresher'];
    }

    public function testGetEmbedsInstructorSlots(): void
    {
        $course = CourseFactory::createOne();
        CourseInstructorFactory::createOne(['course' => $course, 'title' => 'Lead Instructor']);

        $response = $this->get(self::endpoint() . "/{$course->getId()}");

        self::assertSame(['Lead Instructor'], array_column($response['instructors'], 'title'));
    }

    public function testPostInstructorSlot(): void
    {
        $course = CourseFactory::createOne();

        $instructor = $this->post("/api/milhq/courses/{$course->getId()}/instructors", [
            'json' => ['title' => 'Assistant Instructor'],
        ]);

        $instructor = $this->get($instructor['@id']);
        self::assertSame("/api/milhq/courses/{$course->getId()}", $instructor['course']);
    }

    public function testCoursesWithoutAccessAreHidden(): void
    {
        $this->oauthClient = OAuthClientFactory::createOne();
        $course = CourseFactory::createOne();

        self::assertEmpty($this->getCollection(self::endpoint()));

        $this->expectException(ClientException::class);
        $this->expectExceptionCode(403);
        $this->get(self::endpoint() . "/{$course->getId()}");
    }
}
