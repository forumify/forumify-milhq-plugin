<?php

declare(strict_types=1);

namespace PluginTests\Tests\Application;

use Forumify\Milhq\Entity\Rank;
use Forumify\Milhq\Entity\RankGroup;
use Forumify\Testing\Factories\Core\ACLFactory;
use Forumify\Testing\Factories\Core\RoleFactory;
use Forumify\Testing\Factories\Core\UserFactory;
use PluginTests\Tests\Factories\Milhq\RankFactory;
use PluginTests\Tests\Factories\Milhq\SoldierFactory;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

use function Zenstruck\Foundry\Persistence\persist;

class RankVisibilityTest extends MilhqWebTestCase
{
    use InteractsWithLiveComponents;

    public function testHiddenGroupHidesItsTabAndRanks(): void
    {
        $visible = $this->group('Visible Rank Group', ['user']);
        $hidden = $this->group('Hidden Rank Group');
        $this->rank('Visible Group Rank', $visible);
        $this->rank('Hidden Group Rank', $hidden);
        $user = UserFactory::createOne();
        $this->client->loginUser($user);

        $text = $this->client->request('GET', '/milhq/ranks')->text();

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Visible Group Rank', $text);
        self::assertStringNotContainsString('Hidden Rank Group', $text);
        self::assertStringNotContainsString('Hidden Group Rank', $text);

        $this->expectException(AccessDeniedHttpException::class);
        $this->createLiveComponent('RankList', ['groupId' => $hidden->getId()])->actingAs($user)->render();
    }

    public function testUngroupedRanksAreAlwaysVisible(): void
    {
        $this->rank('Loose Rank');
        $this->client->restart();

        $crawler = $this->client->request('GET', '/milhq/ranks');

        self::assertSelectorExists('[data-tab-id="rank-group-ungrouped"]');
        self::assertStringContainsString('Loose Rank', $crawler->text());
    }

    public function testGuestSeesOnlyGuestGrantedGroups(): void
    {
        $this->rank('Guest Visible Rank', $this->group('Guest Rank Group', ['guest', 'user']));
        $this->rank('Members Only Rank', $this->group('Members Rank Group', ['user']));
        $this->client->restart();

        $text = $this->client->request('GET', '/milhq/ranks')->text();

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Guest Visible Rank', $text);
        self::assertStringNotContainsString('Members Only Rank', $text);

        $this->client->loginUser(UserFactory::createOne());
        self::assertStringContainsString('Members Only Rank', $this->client->request('GET', '/milhq/ranks')->text());
    }

    public function testSuperAdminSeesEverything(): void
    {
        $this->rank('Unlisted Rank', $this->group('Unlisted Rank Group'));

        $text = $this->client->request('GET', '/milhq/ranks')->text();

        self::assertStringContainsString('Unlisted Rank Group', $text);
        self::assertStringContainsString('Unlisted Rank', $text);
    }

    public function testProfileStillShowsRanksOfHiddenGroups(): void
    {
        $rank = RankFactory::createOne(['name' => 'Secret Rank', 'abbreviation' => 'SECR', 'group' => $this->group('Secret Rank Group')]);
        $soldier = SoldierFactory::createOne(['rank' => $rank]);
        $this->client->loginUser(UserFactory::createOne());

        $text = $this->client->request('GET', '/milhq/soldier/' . $soldier->getId())->text();

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('SECR', $text);
    }

    public function testEditorLinksToGroupACLOnly(): void
    {
        $group = $this->group('Editor Rank Group');
        $this->rank('Editor Rank', $group);

        $html = urldecode(html_entity_decode($this->createLiveComponent('Milhq\\RankEditor')
            ->actingAs($this->user)
            ->call('toggleGroup', ['groupId' => $group->getId()])
            ->render()
            ->toString()));

        self::assertStringContainsString('entity=' . RankGroup::class . '&entityId=' . $group->getId() . '&', $html);
        self::assertStringNotContainsString('entity=' . Rank::class . '&', $html);
    }

    /**
     * @param list<string> $roles
     */
    private function group(string $title, array $roles = []): RankGroup
    {
        $group = persist(RankGroup::class, ['title' => $title]);
        if (!empty($roles)) {
            ACLFactory::createOne([
                'entity' => RankGroup::class,
                'entityId' => (string)$group->getId(),
                'permission' => 'view',
                'roles' => array_map(static fn (string $slug) => RoleFactory::findOrCreate(['slug' => $slug]), $roles),
            ]);
        }

        return $group;
    }

    private function rank(string $name, ?RankGroup $group = null): Rank
    {
        return RankFactory::createOne(['name' => $name, 'group' => $group]);
    }
}
