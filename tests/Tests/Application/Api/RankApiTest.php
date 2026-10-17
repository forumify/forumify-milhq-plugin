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
use PluginTests\Tests\Factories\Milhq\RankFactory;
use PluginTests\Tests\Factories\Milhq\RankGroupFactory;
use Symfony\Component\HttpClient\Exception\ClientException;

class RankApiTest extends ApiTestCase
{
    use GetCollectionTestTrait;
    use GetTestTrait;
    use PostTestTrait;
    use PatchTestTrait;
    use DeleteTestTrait;

    protected static function factory(): string
    {
        return RankFactory::class;
    }

    protected static function endpoint(): string
    {
        return '/api/milhq/ranks';
    }

    protected function getPostBody(): array
    {
        return [
            [
                'name' => 'Sergeant',
                'abbreviation' => 'SGT',
                'paygrade' => 'E5',
                'newImage' => [
                    'filename' => 'sergeant.png',
                    'data' => base64_encode((string)file_get_contents(TEST_DATA_DIR . '/sergeant.png')),
                ],
            ],
            function (array $data): void {
                self::assertSame('Sergeant', $data['name']);
                self::assertStringStartsWith('/storage/milhq/', $data['image']);
                self::assertStringEndsWith('sergeant.png', $data['image']);
            },
        ];
    }

    protected function getPatchBody(): array
    {
        return ['name' => 'Staff Sergeant'];
    }

    public function testGetCollectionInGroup(): void
    {
        $group = RankGroupFactory::createOne();
        $inGroup = RankFactory::createOne(['group' => $group]);
        RankFactory::createOne();

        $ranks = $this->getCollection("/api/milhq/rank-groups/{$group->getId()}/ranks");

        self::assertSame([$inGroup->getId()], array_column($ranks, 'id'));
    }

    public function testChangingGroupMovesRankToTheEndOfThatGroup(): void
    {
        $group = RankGroupFactory::createOne();
        RankFactory::createOne(['group' => $group, 'position' => 5]);
        $rank = RankFactory::createOne(['position' => 1]);

        $patched = $this->patch(self::endpoint() . "/{$rank->getId()}", [
            'json' => ['group' => "/api/milhq/rank-groups/{$group->getId()}"],
        ]);

        self::assertSame(6, $patched['position']);
    }

    public function testPostRequiresPermission(): void
    {
        $this->oauthClient = OAuthClientFactory::createOne();

        $this->expectException(ClientException::class);
        $this->expectExceptionCode(403);
        $this->post(self::endpoint(), ['json' => ['name' => 'Sergeant']]);
    }
}
