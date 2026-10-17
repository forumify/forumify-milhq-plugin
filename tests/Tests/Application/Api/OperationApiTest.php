<?php

declare(strict_types=1);

namespace PluginTests\Tests\Application\Api;

use DateTime;
use Forumify\Core\Service\TokenService;
use Forumify\Milhq\Entity\Operation;
use Forumify\Milhq\Entity\Soldier;
use Forumify\Milhq\Repository\CombatRecordRepository;
use Forumify\Testing\Api\ApiTestCase;
use Forumify\Testing\Api\Crud\DeleteTestTrait;
use Forumify\Testing\Api\Crud\GetCollectionTestTrait;
use Forumify\Testing\Api\Crud\GetTestTrait;
use Forumify\Testing\Api\Crud\PatchTestTrait;
use Forumify\Testing\Api\Crud\PostTestTrait;
use Forumify\Testing\Factories\Core\ACLFactory;
use Forumify\Testing\Factories\Core\RoleFactory;
use Forumify\Testing\Factories\Core\UserFactory;
use Forumify\Testing\Factories\OAuth\OAuthClientFactory;
use Doctrine\Common\Collections\ArrayCollection;
use PluginTests\Tests\Factories\Milhq\AfterActionReportFactory;
use PluginTests\Tests\Factories\Milhq\MissionFactory;
use PluginTests\Tests\Factories\Milhq\MissionRsvpFactory;
use PluginTests\Tests\Factories\Milhq\OperationFactory;
use PluginTests\Tests\Factories\Milhq\SoldierFactory;
use PluginTests\Tests\Factories\Milhq\UnitFactory;
use Symfony\Component\HttpClient\Exception\ClientException;

class OperationApiTest extends ApiTestCase
{
    use GetCollectionTestTrait;
    use GetTestTrait;
    use PostTestTrait;
    use PatchTestTrait;
    use DeleteTestTrait;

    protected static function factory(): string
    {
        return OperationFactory::class;
    }

    protected static function endpoint(): string
    {
        return '/api/milhq/operations';
    }

    protected function getPostBody(): array
    {
        return [
            'title' => 'Operation Red Dawn',
            'description' => 'Liberate the city',
            'requestRsvp' => true,
        ];
    }

    protected function getPatchBody(): array
    {
        return ['title' => 'Operation Blue Dusk'];
    }

    public function testGetAndPostMissionsOfOperation(): void
    {
        $operation = OperationFactory::createOne(['title' => 'Operation Red Dawn']);
        $existing = MissionFactory::createOne(['operation' => $operation]);
        MissionFactory::createOne();

        $created = $this->post("/api/milhq/operations/{$operation->getId()}/missions", [
            'json' => [
                'title' => 'Mission 1: Landing',
                'start' => '2026-11-08T18:00:00+00:00',
            ],
        ]);
        self::assertSame('Mission 1: Landing', $created['title']);

        $missions = $this->getCollection("/api/milhq/operations/{$operation->getId()}/missions");
        self::assertEqualsCanonicalizing([$existing->getId(), $created['id']], array_column($missions, 'id'));
        self::assertSame('Operation Red Dawn', $missions[0]['operation']['title']);
    }

    public function testSoldierRsvpsToMission(): void
    {
        $user = UserFactory::createOne(['roleEntities' => new ArrayCollection([RoleFactory::findOrCreate(['slug' => 'super-admin'])])]);
        $soldier = SoldierFactory::createOne(['user' => $user, 'name' => 'John Doe']);
        $mission = MissionFactory::createOne();
        $options = ['headers' => ['Authorization' => 'Bearer ' . self::getContainer()->get(TokenService::class)->createJwt($user)]];

        $this->post("/api/milhq/missions/{$mission->getId()}/rsvps", $options + ['json' => ['going' => true]]);
        $rsvp = $this->post("/api/milhq/missions/{$mission->getId()}/rsvps", $options + ['json' => ['going' => false]]);
        self::assertFalse($rsvp['going']);

        $rsvps = $this->getCollection("/api/milhq/missions/{$mission->getId()}/rsvps");
        self::assertCount(1, $rsvps);
        self::assertSame('John Doe', $rsvps[0]['soldier']['name']);
        self::assertFalse($rsvps[0]['going']);

        $this->delete($rsvp['@id'], $options);
        self::assertResponseIsSuccessful();
        self::assertSame(0, MissionRsvpFactory::count(['soldier' => $soldier->getId()]));
    }

    public function testCannotDeleteOtherSoldiersRsvp(): void
    {
        $user = UserFactory::createOne();
        SoldierFactory::createOne(['user' => $user]);
        $rsvp = MissionRsvpFactory::createOne(['soldier' => SoldierFactory::createOne()]);

        $response = $this->request('DELETE', "/api/milhq/mission-rsvps/{$rsvp->getId()}", [
            'headers' => ['Authorization' => 'Bearer ' . self::getContainer()->get(TokenService::class)->createJwt($user)],
        ]);

        self::assertSame(403, $response->getStatusCode());
        self::assertSame(1, MissionRsvpFactory::count(['id' => $rsvp->getId()]));
    }

    public function testMissionViewerCannotCreateMissions(): void
    {
        $operation = OperationFactory::createOne();
        ACLFactory::createOne([
            'entity' => Operation::class,
            'entityId' => (string)$operation->getId(),
            'permission' => 'view_missions',
        ]);
        $this->oauthClient = OAuthClientFactory::createOne();

        $response = $this->request('POST', "/api/milhq/operations/{$operation->getId()}/missions", [
            'json' => ['title' => 'Unauthorized mission', 'start' => '2026-11-08T18:00:00+00:00'],
        ]);

        self::assertSame(403, $response->getStatusCode());
    }

    public function testRsvpRequiresMissionAccess(): void
    {
        $user = UserFactory::createOne();
        SoldierFactory::createOne(['user' => $user]);
        $mission = MissionFactory::createOne();

        $response = $this->request('POST', "/api/milhq/missions/{$mission->getId()}/rsvps", [
            'json' => ['going' => true],
            'headers' => ['Authorization' => 'Bearer ' . self::getContainer()->get(TokenService::class)->createJwt($user)],
        ]);

        self::assertSame(403, $response->getStatusCode());
    }

    public function testRsvpRequiresSoldier(): void
    {
        $mission = MissionFactory::createOne();

        $response = $this->request('POST', "/api/milhq/missions/{$mission->getId()}/rsvps", ['json' => ['going' => true]]);

        self::assertSame(403, $response->getStatusCode());
    }

    public function testCannotRsvpToPastMission(): void
    {
        $user = UserFactory::createOne(['roleEntities' => new ArrayCollection([RoleFactory::findOrCreate(['slug' => 'super-admin'])])]);
        SoldierFactory::createOne(['user' => $user]);
        $mission = MissionFactory::createOne(['start' => new DateTime('-1 day')]);

        $response = $this->request('POST', "/api/milhq/missions/{$mission->getId()}/rsvps", [
            'json' => ['going' => true],
            'headers' => ['Authorization' => 'Bearer ' . self::getContainer()->get(TokenService::class)->createJwt($user)],
        ]);

        self::assertSame(422, $response->getStatusCode());
    }

    public function testPostAfterActionReportCreatesCombatRecords(): void
    {
        $mission = MissionFactory::createOne(['createCombatRecords' => true, 'combatRecordText' => 'Fought bravely']);
        $unit = UnitFactory::createOne(['name' => 'First Squad']);
        /** @var list<Soldier> $soldiers */
        $soldiers = SoldierFactory::createMany(3, ['unit' => $unit]);

        $aar = $this->post("/api/milhq/missions/{$mission->getId()}/after-action-reports", [
            'json' => [
                'unit' => "/api/milhq/units/{$unit->getId()}",
                'report' => 'We won.',
                'attendance' => [
                    'present' => [$soldiers[0]->getId(), $soldiers[1]->getId()],
                    'absent' => [$soldiers[2]->getId()],
                ],
            ],
        ]);

        self::assertSame('We won.', $aar['report']);
        self::assertSame([$soldiers[0]->getId(), $soldiers[1]->getId()], $aar['attendance']['present']);
        self::assertSame([], $aar['attendance']['excused']);

        $combatRecords = self::getContainer()->get(CombatRecordRepository::class)->findBy(['text' => 'Fought bravely']);
        self::assertCount(2, $combatRecords);

        $aars = $this->getCollection("/api/milhq/missions/{$mission->getId()}/after-action-reports");
        self::assertSame('First Squad', $aars[0]['unit']['name']);
    }

    public function testOnlyOneAfterActionReportPerUnit(): void
    {
        $aar = AfterActionReportFactory::createOne();

        $response = $this->request('POST', "/api/milhq/missions/{$aar->getMission()->getId()}/after-action-reports", [
            'json' => ['unit' => "/api/milhq/units/{$aar->getUnit()->getId()}"],
        ]);

        self::assertSame(422, $response->getStatusCode());
    }

    public function testAuthorWithoutAccessCannotManageAfterActionReport(): void
    {
        $author = UserFactory::createOne();
        $aar = AfterActionReportFactory::createOne(['createdBy' => $author, 'report' => 'Original report']);
        $options = ['headers' => ['Authorization' => 'Bearer ' . self::getContainer()->get(TokenService::class)->createJwt($author)]];

        $patch = $this->request('PATCH', "/api/milhq/after-action-reports/{$aar->getId()}", [
            'json' => ['report' => 'Out of spite'],
            'headers' => $options['headers'] + ['Content-Type' => 'application/merge-patch+json'],
        ]);
        $delete = $this->request('DELETE', "/api/milhq/after-action-reports/{$aar->getId()}", $options);

        self::assertSame(403, $patch->getStatusCode());
        self::assertSame(403, $delete->getStatusCode());
        self::assertSame(1, AfterActionReportFactory::count(['report' => 'Original report']));
    }

    public function testViewerCanReadButNotManageAfterActionReports(): void
    {
        $aar = AfterActionReportFactory::createOne(['report' => 'We won.']);
        ACLFactory::createOne([
            'entity' => Operation::class,
            'entityId' => (string)$aar->getMission()->getOperation()->getId(),
            'permission' => 'view_after_action_reports',
        ]);
        $this->oauthClient = OAuthClientFactory::createOne();

        $aars = $this->getCollection("/api/milhq/missions/{$aar->getMission()->getId()}/after-action-reports");
        self::assertSame([$aar->getId()], array_column($aars, 'id'));
        self::assertSame('We won.', $this->get("/api/milhq/after-action-reports/{$aar->getId()}")['report']);

        $patch = $this->request('PATCH', "/api/milhq/after-action-reports/{$aar->getId()}", [
            'json' => ['report' => 'Rewritten'],
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
        ]);
        $delete = $this->request('DELETE', "/api/milhq/after-action-reports/{$aar->getId()}");
        $post = $this->request('POST', "/api/milhq/missions/{$aar->getMission()->getId()}/after-action-reports", [
            'json' => ['unit' => '/api/milhq/units/' . UnitFactory::createOne()->getId()],
        ]);

        self::assertSame(403, $patch->getStatusCode());
        self::assertSame(403, $delete->getStatusCode());
        self::assertSame(403, $post->getStatusCode());
    }

    public function testAfterActionReportsWithoutAccessAreHidden(): void
    {
        $aar = AfterActionReportFactory::createOne();
        $this->oauthClient = OAuthClientFactory::createOne();

        self::assertEmpty($this->getCollection("/api/milhq/missions/{$aar->getMission()->getId()}/after-action-reports"));

        $this->expectException(ClientException::class);
        $this->expectExceptionCode(403);
        $this->get("/api/milhq/after-action-reports/{$aar->getId()}");
    }

    public function testMissionsWithoutAccessAreHidden(): void
    {
        $this->oauthClient = OAuthClientFactory::createOne();
        $mission = MissionFactory::createOne();

        self::assertEmpty($this->getCollection("/api/milhq/operations/{$mission->getOperation()->getId()}/missions"));

        $this->expectException(ClientException::class);
        $this->expectExceptionCode(403);
        $this->get("/api/milhq/missions/{$mission->getId()}");
    }
}
