<?php

declare(strict_types=1);

namespace PluginTests\Tests\Application;

use Forumify\Milhq\Entity\Qualification;
use Forumify\Milhq\Entity\QualificationGroup;
use Forumify\Testing\Factories\Core\ACLFactory;
use Forumify\Testing\Factories\Core\RoleFactory;
use Forumify\Testing\Factories\Core\UserFactory;
use PluginTests\Tests\Factories\Milhq\QualificationFactory;
use PluginTests\Tests\Factories\Milhq\Record\QualificationRecordFactory;
use PluginTests\Tests\Factories\Milhq\SoldierFactory;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

use function Zenstruck\Foundry\Persistence\persist;

class QualificationVisibilityTest extends MilhqWebTestCase
{
    use InteractsWithLiveComponents;

    public function testHiddenGroupHidesItsTabAndQualifications(): void
    {
        $visible = $this->group('Visible Qualification Group', ['user']);
        $hidden = $this->group('Hidden Qualification Group');
        $this->qualification('Visible Group Qualification', $visible);
        $this->qualification('Hidden Group Qualification', $hidden);
        $user = UserFactory::createOne();
        $this->client->loginUser($user);

        $text = $this->client->request('GET', '/milhq/qualifications')->text();

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Visible Group Qualification', $text);
        self::assertStringNotContainsString('Hidden Qualification Group', $text);
        self::assertStringNotContainsString('Hidden Group Qualification', $text);

        $this->expectException(AccessDeniedHttpException::class);
        $this->createLiveComponent('Milhq\\QualificationList', ['groupId' => $hidden->getId()])->actingAs($user)->render();
    }

    public function testUngroupedQualificationsAreAlwaysVisible(): void
    {
        $this->qualification('Loose Qualification');
        $this->client->restart();

        $crawler = $this->client->request('GET', '/milhq/qualifications');

        self::assertSelectorExists('[data-tab-id="qualification-group-ungrouped"]');
        self::assertStringContainsString('Loose Qualification', $crawler->text());
    }

    public function testGuestSeesOnlyGuestGrantedGroups(): void
    {
        $this->qualification('Guest Visible Qualification', $this->group('Guest Qualification Group', ['guest', 'user']));
        $this->qualification('Members Only Qualification', $this->group('Members Qualification Group', ['user']));
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
        $this->qualification('Unlisted Qualification', $this->group('Unlisted Qualification Group'));

        $text = $this->client->request('GET', '/milhq/qualifications')->text();

        self::assertStringContainsString('Unlisted Qualification Group', $text);
        self::assertStringContainsString('Unlisted Qualification', $text);
    }

    public function testProfileStillShowsQualificationsOfHiddenGroups(): void
    {
        $soldier = SoldierFactory::createOne();
        QualificationRecordFactory::createOne([
            'soldier' => $soldier,
            'qualification' => $this->qualification('Secret Qualification', $this->group('Secret Qualification Group')),
        ]);
        $this->client->loginUser(UserFactory::createOne());

        $this->client->request('GET', '/milhq/soldier/' . $soldier->getId());

        self::assertSelectorTextContains('#qualification-record', 'Secret Qualification');
    }

    public function testEditorLinksToGroupACLOnly(): void
    {
        $group = $this->group('Editor Qualification Group');
        $this->qualification('Editor Qualification', $group);

        $html = urldecode(html_entity_decode($this->createLiveComponent('Milhq\\QualificationEditor')
            ->actingAs($this->user)
            ->call('toggleGroup', ['groupId' => $group->getId()])
            ->render()
            ->toString()));

        self::assertStringContainsString('entity=' . QualificationGroup::class . '&entityId=' . $group->getId() . '&', $html);
        self::assertStringNotContainsString('entity=' . Qualification::class . '&', $html);
    }

    /**
     * @param list<string> $roles
     */
    private function group(string $title, array $roles = []): QualificationGroup
    {
        $group = persist(QualificationGroup::class, ['title' => $title]);
        if (!empty($roles)) {
            ACLFactory::createOne([
                'entity' => QualificationGroup::class,
                'entityId' => (string)$group->getId(),
                'permission' => 'view',
                'roles' => array_map(static fn (string $slug) => RoleFactory::findOrCreate(['slug' => $slug]), $roles),
            ]);
        }

        return $group;
    }

    private function qualification(string $name, ?QualificationGroup $group = null): Qualification
    {
        return QualificationFactory::createOne(['name' => $name, 'group' => $group]);
    }
}
