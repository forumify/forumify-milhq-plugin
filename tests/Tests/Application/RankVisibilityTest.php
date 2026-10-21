<?php

declare(strict_types=1);

namespace PluginTests\Tests\Application;

use Forumify\Core\Entity\AccessControlledEntityInterface;
use Forumify\Milhq\Entity\RankGroup;
use Forumify\Testing\Factories\Core\ACLFactory;
use Forumify\Testing\Factories\Core\RoleFactory;
use Forumify\Testing\Factories\Core\UserFactory;
use PluginTests\Tests\Factories\Milhq\RankFactory;
use PluginTests\Tests\Factories\Milhq\SoldierFactory;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

use function Zenstruck\Foundry\Persistence\persist;

class RankVisibilityTest extends MilhqWebTestCase
{
    use InteractsWithLiveComponents;

    public function testHiddenGroupHidesItsTabAndRanks(): void
    {
        $visible = $this->group('Visible Group', ['user']);
        $hidden = $this->group('Hidden Group');
        $this->rank('Visible Group Rank', $visible, ['user']);
        $this->rank('Hidden Group Rank', $hidden, ['user']);
        $user = UserFactory::createOne();
        $this->client->loginUser($user);

        $text = $this->client->request('GET', '/milhq/ranks')->text();

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Visible Group Rank', $text);
        self::assertStringNotContainsString('Hidden Group', $text);

        $list = $this->createLiveComponent('RankList', ['groupId' => $hidden->getId()])
            ->actingAs($user)
            ->render()
            ->toString();
        self::assertStringNotContainsString('Hidden Group Rank', $list);
    }

    public function testHiddenRankInVisibleGroupIsHidden(): void
    {
        $group = $this->group('Officer Ranks Group', ['user']);
        $this->rank('Visible Captain Rank', $group, ['user']);
        $this->rank('Secret Rank', $group);
        $this->client->loginUser(UserFactory::createOne());

        $text = $this->client->request('GET', '/milhq/ranks')->text();

        self::assertStringContainsString('Visible Captain Rank', $text);
        self::assertStringNotContainsString('Secret Rank', $text);
    }

    public function testUngroupedRanksFollowOnlyTheirOwnACL(): void
    {
        $this->rank('Loose Rank', null, ['user']);
        $this->rank('Hidden Loose Rank');
        $this->client->loginUser(UserFactory::createOne());

        $crawler = $this->client->request('GET', '/milhq/ranks');

        self::assertSelectorExists('[data-tab-id="rank-group-ungrouped"]');
        self::assertStringContainsString('Loose Rank', $crawler->text());
        self::assertStringNotContainsString('Hidden Loose Rank', $crawler->text());
    }

    public function testUngroupedTabIsHiddenWithoutViewableUngroupedRanks(): void
    {
        $this->rank('Hidden Loose Rank');
        $this->rank('Grouped Rank', $this->group('Officer Ranks Group', ['user']), ['user']);
        $this->client->loginUser(UserFactory::createOne());

        $this->client->request('GET', '/milhq/ranks');

        self::assertSelectorNotExists('[data-tab-id="rank-group-ungrouped"]');
    }

    public function testGuestSeesOnlyGuestGrantedRanks(): void
    {
        $group = $this->group('Enlisted Ranks Group', ['guest', 'user']);
        $this->rank('Guest Visible Rank', $group, ['guest', 'user']);
        $this->rank('Members Only Rank', $group, ['user']);
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
        $this->rank('Unlisted Rank', $this->group('Unlisted Group'));

        $text = $this->client->request('GET', '/milhq/ranks')->text();

        self::assertStringContainsString('Unlisted Group', $text);
        self::assertStringContainsString('Unlisted Rank', $text);
    }

    public function testProfileStillShowsHiddenRanks(): void
    {
        $rank = RankFactory::createOne(['name' => 'Secret Rank', 'abbreviation' => 'SECR', 'group' => $this->group('Hidden Group')]);
        $soldier = SoldierFactory::createOne(['rank' => $rank]);
        $this->client->loginUser(UserFactory::createOne());

        $text = $this->client->request('GET', '/milhq/soldier/' . $soldier->getId())->text();

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('SECR', $text);
    }

    public function testRankEditorLinksToGroupAndRankACL(): void
    {
        $group = $this->group('Officer Ranks Group');
        $rank = $this->rank('Visible Captain Rank', $group);

        $html = html_entity_decode($this->createLiveComponent('Milhq\\RankEditor')
            ->actingAs($this->user)
            ->call('toggleGroup', ['groupId' => $group->getId()])
            ->render()
            ->toString());

        self::assertStringContainsString('entity=' . RankGroup::class . '&entityId=' . $group->getId() . '&', urldecode($html));
        self::assertStringContainsString('entity=' . get_class($rank) . '&entityId=' . $rank->getId() . '&', urldecode($html));
    }

    /**
     * @param list<string> $roles
     */
    private function group(string $title, array $roles = []): RankGroup
    {
        $group = persist(RankGroup::class, ['title' => $title]);
        $this->grant($group, $roles);

        return $group;
    }

    /**
     * @param list<string> $roles
     */
    private function rank(string $name, ?RankGroup $group = null, array $roles = []): object
    {
        $rank = RankFactory::createOne(['name' => $name, 'group' => $group]);
        $this->grant($rank, $roles);

        return $rank;
    }

    /**
     * @param list<string> $roles
     */
    private function grant(AccessControlledEntityInterface $entity, array $roles): void
    {
        if (empty($roles)) {
            return;
        }

        ACLFactory::createOne([
            'entity' => $entity::class,
            'entityId' => $entity->getACLParameters()->entityId,
            'permission' => 'view',
            'roles' => array_map(static fn (string $slug) => RoleFactory::findOrCreate(['slug' => $slug]), $roles),
        ]);
    }
}
