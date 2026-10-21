<?php

declare(strict_types=1);

namespace PluginTests\Tests\Application;

use Forumify\Core\Entity\AccessControlledEntityInterface;
use Forumify\Milhq\Entity\QualificationGroup;
use Forumify\Testing\Factories\Core\ACLFactory;
use Forumify\Testing\Factories\Core\RoleFactory;
use Forumify\Testing\Factories\Core\UserFactory;
use PluginTests\Tests\Factories\Milhq\QualificationFactory;
use PluginTests\Tests\Factories\Milhq\Record\QualificationRecordFactory;
use PluginTests\Tests\Factories\Milhq\SoldierFactory;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

use function Zenstruck\Foundry\Persistence\persist;

class QualificationVisibilityTest extends MilhqWebTestCase
{
    use InteractsWithLiveComponents;

    public function testHiddenGroupHidesItsTabAndQualifications(): void
    {
        $visible = $this->group('Visible Group', ['user']);
        $hidden = $this->group('Hidden Group');
        $this->qualification('Visible Group Qualification', $visible, ['user']);
        $this->qualification('Hidden Group Qualification', $hidden, ['user']);
        $user = UserFactory::createOne();
        $this->client->loginUser($user);

        $text = $this->client->request('GET', '/milhq/qualifications')->text();

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Visible Group Qualification', $text);
        self::assertStringNotContainsString('Hidden Group', $text);

        $list = $this->createLiveComponent('Milhq\\QualificationList', ['groupId' => $hidden->getId()])
            ->actingAs($user)
            ->render()
            ->toString();
        self::assertStringNotContainsString('Hidden Group Qualification', $list);
    }

    public function testHiddenQualificationInVisibleGroupIsHidden(): void
    {
        $group = $this->group('Instructor Qualifications Group', ['user']);
        $this->qualification('Visible Instructor Qualification', $group, ['user']);
        $this->qualification('Secret Qualification', $group);
        $this->client->loginUser(UserFactory::createOne());

        $text = $this->client->request('GET', '/milhq/qualifications')->text();

        self::assertStringContainsString('Visible Instructor Qualification', $text);
        self::assertStringNotContainsString('Secret Qualification', $text);
    }

    public function testUngroupedQualificationsFollowOnlyTheirOwnACL(): void
    {
        $this->qualification('Loose Qualification', null, ['user']);
        $this->qualification('Hidden Loose Qualification');
        $this->client->loginUser(UserFactory::createOne());

        $crawler = $this->client->request('GET', '/milhq/qualifications');

        self::assertSelectorExists('[data-tab-id="qualification-group-ungrouped"]');
        self::assertStringContainsString('Loose Qualification', $crawler->text());
        self::assertStringNotContainsString('Hidden Loose Qualification', $crawler->text());
    }

    public function testUngroupedTabIsHiddenWithoutViewableUngroupedQualifications(): void
    {
        $this->qualification('Hidden Loose Qualification');
        $this->qualification('Grouped Qualification', $this->group('Instructor Qualifications Group', ['user']), ['user']);
        $this->client->loginUser(UserFactory::createOne());

        $this->client->request('GET', '/milhq/qualifications');

        self::assertSelectorNotExists('[data-tab-id="qualification-group-ungrouped"]');
    }

    public function testGuestSeesOnlyGuestGrantedQualifications(): void
    {
        $group = $this->group('Basic Qualifications Group', ['guest', 'user']);
        $this->qualification('Guest Visible Qualification', $group, ['guest', 'user']);
        $this->qualification('Members Only Qualification', $group, ['user']);
        $this->client->restart();

        $text = $this->client->request('GET', '/milhq/qualifications')->text();

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Guest Visible Qualification', $text);
        self::assertStringNotContainsString('Members Only Qualification', $text);

        $this->client->loginUser(UserFactory::createOne());
        self::assertStringContainsString('Members Only Qualification', $this->client->request('GET', '/milhq/qualifications')->text());
    }

    public function testSuperAdminSeesEverything(): void
    {
        $this->qualification('Unlisted Qualification', $this->group('Unlisted Group'));

        $text = $this->client->request('GET', '/milhq/qualifications')->text();

        self::assertStringContainsString('Unlisted Group', $text);
        self::assertStringContainsString('Unlisted Qualification', $text);
    }

    public function testProfileStillShowsHiddenQualifications(): void
    {
        $soldier = SoldierFactory::createOne();
        QualificationRecordFactory::createOne([
            'soldier' => $soldier,
            'qualification' => $this->qualification('Secret Qualification', $this->group('Hidden Group')),
        ]);
        $this->client->loginUser(UserFactory::createOne());

        $this->client->request('GET', '/milhq/soldier/' . $soldier->getId());

        self::assertSelectorTextContains('#qualification-record', 'Secret Qualification');
    }

    public function testQualificationEditorLinksToGroupAndQualificationACL(): void
    {
        $group = $this->group('Instructor Qualifications Group');
        $qualification = $this->qualification('Visible Instructor Qualification', $group);

        $html = html_entity_decode($this->createLiveComponent('Milhq\\QualificationEditor')
            ->actingAs($this->user)
            ->call('toggleGroup', ['groupId' => $group->getId()])
            ->render()
            ->toString());

        self::assertStringContainsString('entity=' . QualificationGroup::class . '&entityId=' . $group->getId() . '&', urldecode($html));
        self::assertStringContainsString('entity=' . get_class($qualification) . '&entityId=' . $qualification->getId() . '&', urldecode($html));
    }

    /**
     * @param list<string> $roles
     */
    private function group(string $title, array $roles = []): QualificationGroup
    {
        $group = persist(QualificationGroup::class, ['title' => $title]);
        $this->grant($group, $roles);

        return $group;
    }

    /**
     * @param list<string> $roles
     */
    private function qualification(string $name, ?QualificationGroup $group = null, array $roles = []): object
    {
        $qualification = QualificationFactory::createOne(['name' => $name, 'group' => $group]);
        $this->grant($qualification, $roles);

        return $qualification;
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
