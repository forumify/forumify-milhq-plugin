<?php

declare(strict_types=1);

namespace PluginTests\Tests\Application;

use Forumify\Milhq\Entity\Award;
use Forumify\Milhq\Entity\AwardGroup;
use Forumify\Testing\Factories\Core\ACLFactory;
use Forumify\Testing\Factories\Core\RoleFactory;
use Forumify\Testing\Factories\Core\UserFactory;
use PluginTests\Tests\Factories\Milhq\AwardFactory;
use PluginTests\Tests\Factories\Milhq\Record\AwardRecordFactory;
use PluginTests\Tests\Factories\Milhq\SoldierFactory;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

use function Zenstruck\Foundry\Persistence\persist;

class AwardVisibilityTest extends MilhqWebTestCase
{
    use InteractsWithLiveComponents;

    public function testHiddenGroupHidesItsTabAndAwards(): void
    {
        $visible = $this->group('Visible Award Group', ['user']);
        $hidden = $this->group('Hidden Award Group');
        $this->award('Visible Group Award', $visible);
        $this->award('Hidden Group Award', $hidden);
        $user = UserFactory::createOne();
        $this->client->loginUser($user);

        $text = $this->client->request('GET', '/milhq/awards')->text();

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Visible Group Award', $text);
        self::assertStringNotContainsString('Hidden Award Group', $text);
        self::assertStringNotContainsString('Hidden Group Award', $text);

        $this->expectException(AccessDeniedHttpException::class);
        $this->createLiveComponent('AwardList', ['groupId' => $hidden->getId()])->actingAs($user)->render();
    }

    public function testUngroupedAwardsAreAlwaysVisible(): void
    {
        $this->award('Loose Award');
        $this->client->restart();

        $crawler = $this->client->request('GET', '/milhq/awards');

        self::assertSelectorExists('[data-tab-id="award-group-ungrouped"]');
        self::assertStringContainsString('Loose Award', $crawler->text());
    }

    public function testGuestSeesOnlyGuestGrantedGroups(): void
    {
        $this->award('Guest Visible Award', $this->group('Guest Award Group', ['guest', 'user']));
        $this->award('Members Only Award', $this->group('Members Award Group', ['user']));
        $this->client->restart();

        $text = $this->client->request('GET', '/milhq/awards')->text();

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Guest Visible Award', $text);
        self::assertStringNotContainsString('Members Only Award', $text);

        $this->client->loginUser(UserFactory::createOne());
        self::assertStringContainsString('Members Only Award', $this->client->request('GET', '/milhq/awards')->text());
    }

    public function testSuperAdminSeesEverything(): void
    {
        $this->award('Unlisted Award', $this->group('Unlisted Award Group'));

        $text = $this->client->request('GET', '/milhq/awards')->text();

        self::assertStringContainsString('Unlisted Award Group', $text);
        self::assertStringContainsString('Unlisted Award', $text);
    }

    public function testProfileStillShowsAwardsOfHiddenGroups(): void
    {
        $soldier = SoldierFactory::createOne();
        AwardRecordFactory::createOne([
            'soldier' => $soldier,
            'award' => $this->award('Secret Award', $this->group('Secret Award Group')),
        ]);
        $this->client->loginUser(UserFactory::createOne());

        $this->client->request('GET', '/milhq/soldier/' . $soldier->getId());

        self::assertSelectorExists('#assignment [title="Secret Award"]');
    }

    public function testEditorLinksToGroupACLOnly(): void
    {
        $group = $this->group('Editor Award Group');
        $this->award('Editor Award', $group);

        $html = urldecode(html_entity_decode($this->createLiveComponent('Milhq\\AwardEditor')
            ->actingAs($this->user)
            ->call('toggleGroup', ['groupId' => $group->getId()])
            ->render()
            ->toString()));

        self::assertStringContainsString('entity=' . AwardGroup::class . '&entityId=' . $group->getId() . '&', $html);
        self::assertStringNotContainsString('entity=' . Award::class . '&', $html);
    }

    /**
     * @param list<string> $roles
     */
    private function group(string $title, array $roles = []): AwardGroup
    {
        $group = persist(AwardGroup::class, ['title' => $title]);
        if (!empty($roles)) {
            ACLFactory::createOne([
                'entity' => AwardGroup::class,
                'entityId' => (string)$group->getId(),
                'permission' => 'view',
                'roles' => array_map(static fn (string $slug) => RoleFactory::findOrCreate(['slug' => $slug]), $roles),
            ]);
        }

        return $group;
    }

    private function award(string $name, ?AwardGroup $group = null): Award
    {
        return AwardFactory::createOne(['name' => $name, 'group' => $group, 'image' => 'awards/ribbon.png']);
    }
}
