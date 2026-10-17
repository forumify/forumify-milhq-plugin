<?php

declare(strict_types=1);

namespace PluginTests\Tests\Application\Api;

use Forumify\Testing\Api\ApiTestCase;
use Forumify\Testing\Api\Crud\DeleteTestTrait;
use Forumify\Testing\Api\Crud\GetCollectionTestTrait;
use Forumify\Testing\Api\Crud\GetTestTrait;
use Forumify\Testing\Factories\OAuth\OAuthClientFactory;
use PluginTests\Tests\Factories\Milhq\AwardFactory;
use PluginTests\Tests\Factories\Milhq\AwardTierFactory;
use PluginTests\Tests\Factories\Milhq\QualificationFactory;
use PluginTests\Tests\Factories\Milhq\QualificationTierFactory;
use PluginTests\Tests\Factories\Milhq\RankFactory;
use PluginTests\Tests\Factories\Milhq\Record\ServiceRecordFactory;
use PluginTests\Tests\Factories\Milhq\SoldierFactory;
use Symfony\Component\HttpClient\Exception\ClientException;

class RecordApiTest extends ApiTestCase
{
    use GetCollectionTestTrait;
    use GetTestTrait;
    use DeleteTestTrait;

    protected static function factory(): string
    {
        return ServiceRecordFactory::class;
    }

    protected static function endpoint(): string
    {
        return '/api/milhq/service-records';
    }

    public function testPostServiceRecord(): void
    {
        $soldier = SoldierFactory::createOne(['name' => 'John Doe']);

        $record = $this->post(self::endpoint(), [
            'json' => [
                'soldier' => "/api/milhq/soldiers/{$soldier->getId()}",
                'text' => 'Graduated basic training',
                'sendNotification' => true,
            ],
        ]);

        self::assertSame('Graduated basic training', $record['text']);
        self::assertSame('John Doe', $record['soldier']['name']);
        self::assertArrayNotHasKey('sendNotification', $record);
    }

    public function testPostRankRecordPromotesSoldier(): void
    {
        $rank = RankFactory::createOne(['name' => 'Corporal']);
        $soldier = SoldierFactory::createOne();

        $record = $this->post('/api/milhq/rank-records', [
            'json' => [
                'soldier' => "/api/milhq/soldiers/{$soldier->getId()}",
                'rank' => "/api/milhq/ranks/{$rank->getId()}",
                'type' => 'promotion',
            ],
        ]);

        self::assertSame('Corporal', $record['rank']['name']);
        self::assertSame('Corporal', $this->get("/api/milhq/soldiers/{$soldier->getId()}")['rank']['name']);
    }

    public function testPostAwardRecordAutoAdvancesTiers(): void
    {
        $award = AwardFactory::createOne(['name' => 'Good Conduct', 'autoAdvanceTiers' => true]);
        AwardTierFactory::createOne(['parent' => $award, 'name' => 'Bronze', 'position' => 2]);
        AwardTierFactory::createOne(['parent' => $award, 'name' => 'Silver', 'position' => 1]);
        $soldier = SoldierFactory::createOne();

        $body = ['json' => [
            'soldier' => "/api/milhq/soldiers/{$soldier->getId()}",
            'award' => "/api/milhq/awards/{$award->getId()}",
        ]];
        $first = $this->post('/api/milhq/award-records', $body);
        $second = $this->post('/api/milhq/award-records', $body);

        self::assertSame('Good Conduct', $first['award']['name']);
        self::assertSame('Bronze', $first['tier']['name']);
        self::assertSame('Silver', $second['tier']['name']);
    }

    public function testAwardRecordTierMustBelongToAward(): void
    {
        $award = AwardFactory::createOne();
        AwardTierFactory::createOne(['parent' => $award]);
        $soldier = '/api/milhq/soldiers/' . SoldierFactory::createOne()->getId();

        $missing = $this->request('POST', '/api/milhq/award-records', [
            'json' => ['soldier' => $soldier, 'award' => "/api/milhq/awards/{$award->getId()}"],
        ]);
        $foreign = $this->request('POST', '/api/milhq/award-records', [
            'json' => [
                'soldier' => $soldier,
                'award' => "/api/milhq/awards/{$award->getId()}",
                'tier' => '/api/milhq/award-tiers/' . AwardTierFactory::createOne()->getId(),
            ],
        ]);

        self::assertSame(422, $missing->getStatusCode());
        self::assertSame(422, $foreign->getStatusCode());
    }

    public function testQualificationRecordTierMustBelongToQualification(): void
    {
        $qualification = QualificationFactory::createOne();
        $tier = QualificationTierFactory::createOne(['parent' => $qualification]);
        $soldier = '/api/milhq/soldiers/' . SoldierFactory::createOne()->getId();
        $qualificationIri = "/api/milhq/qualifications/{$qualification->getId()}";

        $missing = $this->request('POST', '/api/milhq/qualification-records', [
            'json' => ['soldier' => $soldier, 'qualification' => $qualificationIri],
        ]);
        $foreign = $this->request('POST', '/api/milhq/qualification-records', [
            'json' => [
                'soldier' => $soldier,
                'qualification' => $qualificationIri,
                'tier' => '/api/milhq/qualification-tiers/' . QualificationTierFactory::createOne()->getId(),
            ],
        ]);
        $valid = $this->post('/api/milhq/qualification-records', [
            'json' => ['soldier' => $soldier, 'qualification' => $qualificationIri, 'tier' => "/api/milhq/qualification-tiers/{$tier->getId()}"],
        ]);

        self::assertSame(422, $missing->getStatusCode());
        self::assertSame(422, $foreign->getStatusCode());
        self::assertSame($tier->getId(), $valid['tier']['id']);
    }

    public function testPostRejectsInvalidRankRecordType(): void
    {
        $response = $this->request('POST', '/api/milhq/rank-records', [
            'json' => [
                'soldier' => '/api/milhq/soldiers/' . SoldierFactory::createOne()->getId(),
                'rank' => '/api/milhq/ranks/' . RankFactory::createOne()->getId(),
                'type' => 'sideways',
            ],
        ]);

        self::assertSame(422, $response->getStatusCode());
    }

    public function testGetSoldierRecords(): void
    {
        $soldier = SoldierFactory::createOne();
        $record = ServiceRecordFactory::createOne(['soldier' => $soldier]);
        ServiceRecordFactory::createOne();

        $records = $this->getCollection("/api/milhq/soldiers/{$soldier->getId()}/service-records");

        self::assertSame([$record->getId()], array_column($records, 'id'));
    }

    public function testPostRequiresPermission(): void
    {
        $this->oauthClient = OAuthClientFactory::createOne();

        $this->expectException(ClientException::class);
        $this->expectExceptionCode(403);
        $this->post(self::endpoint(), [
            'json' => ['soldier' => '/api/milhq/soldiers/' . SoldierFactory::createOne()->getId()],
        ]);
    }
}
