<?php

declare(strict_types=1);

namespace PluginTests\Tests\Application;

use Forumify\Core\Entity\AccessControlledEntityInterface;
use Forumify\Milhq\Entity\AwardGroup;
use Forumify\Testing\Factories\Core\ACLFactory;
use Forumify\Testing\Factories\Core\RoleFactory;
use Forumify\Testing\Factories\Core\UserFactory;
use PluginTests\Tests\Factories\Milhq\AwardFactory;
use PluginTests\Tests\Factories\Milhq\Record\AwardRecordFactory;
use PluginTests\Tests\Factories\Milhq\SoldierFactory;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

use function Zenstruck\Foundry\Persistence\persist;

class AwardVisibilityTest extends MilhqWebTestCase
{
    use InteractsWithLiveComponents;

    public function testHiddenGroupHidesItsTabAndAwards(): void
    {
        $visible = $this->group('Visible Group', ['user']);
        $hidden = $this->group('Hidden Group');
        $this->award('Visible Group Award', $visible, ['user']);
        $this->award('Hidden Group Award', $hidden, ['user']);
        $user = UserFactory::createOne();
        $this->client->loginUser($user);

        $text = $this->client->request('GET', '/milhq/awards')->text();

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Visible Group Award', $text);
        self::assertStringNotContainsString('Hidden Group', $text);

        $list = $this->createLiveComponent('AwardList', ['groupId' => $hidden->getId()])
            ->actingAs($user)
            ->render()
            ->toString();
        self::assertStringNotContainsString('Hidden Group Award', $list);
    }

    public function testHiddenAwardInVisibleGroupIsHidden(): void
    {
        $group = $this->group('Command Awards', ['user']);
        $this->award('Shown Medal', $group, ['user']);
        $this->award('Secret Medal', $group);
        $this->client->loginUser(UserFactory::createOne());

        $text = $this->client->request('GET', '/milhq/awards')->text();

        self::assertStringContainsString('Shown Medal', $text);
        self::assertStringNotContainsString('Secret Medal', $text);
    }

    public function testUngroupedAwardsFollowOnlyTheirOwnACL(): void
    {
        $this->award('Loose Ribbon', null, ['user']);
        $this->award('Hidden Loose Ribbon');
        $this->client->loginUser(UserFactory::createOne());

        $crawler = $this->client->request('GET', '/milhq/awards');

        self::assertSelectorExists('[data-tab-id="award-group-ungrouped"]');
        self::assertStringContainsString('Loose Ribbon', $crawler->text());
        self::assertStringNotContainsString('Hidden Loose Ribbon', $crawler->text());
    }

    public function testUngroupedTabIsHiddenWithoutViewableUngroupedAwards(): void
    {
        $this->award('Hidden Loose Ribbon');
        $this->award('Grouped Medal', $this->group('Command Awards', ['user']), ['user']);
        $this->client->loginUser(UserFactory::createOne());

        $this->client->request('GET', '/milhq/awards');

        self::assertSelectorNotExists('[data-tab-id="award-group-ungrouped"]');
    }

    public function testGuestSeesOnlyGuestGrantedAwards(): void
    {
        $group = $this->group('Public Awards', ['guest', 'user']);
        $this->award('Everyone Medal', $group, ['guest', 'user']);
        $this->award('Members Medal', $group, ['user']);
        $this->client->restart();

        $text = $this->client->request('GET', '/milhq/awards')->text();

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Everyone Medal', $text);
        self::assertStringNotContainsString('Members Medal', $text);

        $this->client->loginUser(UserFactory::createOne());
        self::assertStringContainsString('Members Medal', $this->client->request('GET', '/milhq/awards')->text());
    }

    public function testSuperAdminSeesEverything(): void
    {
        $this->award('Unlisted Medal', $this->group('Unlisted Group'));

        $text = $this->client->request('GET', '/milhq/awards')->text();

        self::assertStringContainsString('Unlisted Group', $text);
        self::assertStringContainsString('Unlisted Medal', $text);
    }

    public function testProfileStillShowsHiddenAwards(): void
    {
        $soldier = SoldierFactory::createOne();
        AwardRecordFactory::createOne([
            'soldier' => $soldier,
            'award' => $this->award('Secret Medal', $this->group('Hidden Group')),
        ]);
        $this->client->loginUser(UserFactory::createOne());

        $this->client->request('GET', '/milhq/soldier/' . $soldier->getId());

        self::assertSelectorExists('#assignment [title="Secret Medal"]');
    }

    /**
     * @param list<string> $roles
     */
    private function group(string $title, array $roles = []): AwardGroup
    {
        $group = persist(AwardGroup::class, ['title' => $title]);
        $this->grant($group, $roles);

        return $group;
    }

    /**
     * @param list<string> $roles
     */
    private function award(string $name, ?AwardGroup $group = null, array $roles = []): object
    {
        $award = AwardFactory::createOne(['name' => $name, 'group' => $group, 'image' => 'awards/ribbon.png']);
        $this->grant($award, $roles);

        return $award;
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
