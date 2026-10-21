<?php

declare(strict_types=1);

namespace PluginTests\Tests\Application;

use Forumify\Milhq\Entity\AfterActionReportAttendance;
use Forumify\Milhq\Entity\AwardGroup;
use Forumify\Milhq\Entity\Course;
use Forumify\Milhq\Entity\Operation;
use Forumify\Milhq\Entity\Record\AssignmentRecord;
use Forumify\Testing\Factories\Core\ACLFactory;
use Forumify\Testing\Factories\Core\UserFactory;
use PluginTests\Tests\Factories\Milhq\AfterActionReportFactory;
use PluginTests\Tests\Factories\Milhq\AwardFactory;
use PluginTests\Tests\Factories\Milhq\AwardTierFactory;
use PluginTests\Tests\Factories\Milhq\CourseClassFactory;
use PluginTests\Tests\Factories\Milhq\CourseFactory;
use PluginTests\Tests\Factories\Milhq\MissionFactory;
use PluginTests\Tests\Factories\Milhq\OperationFactory;
use PluginTests\Tests\Factories\Milhq\QualificationFactory;
use PluginTests\Tests\Factories\Milhq\PositionFactory;
use PluginTests\Tests\Factories\Milhq\QualificationTierFactory;
use PluginTests\Tests\Factories\Milhq\Record\AssignmentRecordFactory;
use PluginTests\Tests\Factories\Milhq\Record\AwardRecordFactory;
use PluginTests\Tests\Factories\Milhq\Record\CombatRecordFactory;
use PluginTests\Tests\Factories\Milhq\Record\QualificationRecordFactory;
use PluginTests\Tests\Factories\Milhq\SoldierFactory;
use PluginTests\Tests\Factories\Milhq\UnitFactory;
use PluginTests\Tests\Factories\Stories\MilsimStory;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

use function Zenstruck\Foundry\Persistence\persist;

class SoldierTest extends MilhqWebTestCase
{
    use InteractsWithLiveComponents;

    public function testSoldier(): void
    {
        $soldier = MilsimStory::firstSquad()[5];

        $this->client->request('get', '/milhq/soldier/' . $soldier->getId());

        self::assertResponseIsSuccessful();

        self::assertSelectorTextContains('#specialty', $soldier->getSpecialty()->getName());
        self::assertSelectorTextContains('#unit', $soldier->getUnit()->getName());
        self::assertSelectorTextContains('#position', $soldier->getPosition()->getName());
        self::assertSelectorTextContains('#primary-weapons', 'M4');
        self::assertSelectorTextContains('#secondary-weapons', 'M17');
        self::assertSelectorTextContains('#vehicles', 'HMMWV');
        self::assertSelectorCount(3, '#supervisors > li');
    }

    public function testCombatRecordLinksToMissionWhenAllowed(): void
    {
        $soldier = SoldierFactory::createOne();
        $visibleOperation = OperationFactory::createOne();
        $visibleMission = MissionFactory::createOne(['operation' => $visibleOperation]);
        $hiddenMission = MissionFactory::createOne(['operation' => OperationFactory::createOne()]);
        foreach ([$visibleMission, $hiddenMission] as $mission) {
            CombatRecordFactory::createOne([
                'soldier' => $soldier,
                'afterActionReport' => AfterActionReportFactory::createOne([
                    'mission' => $mission,
                    'attendance' => [new AfterActionReportAttendance($soldier, 'present')],
                ]),
            ]);
        }
        CombatRecordFactory::createOne(['soldier' => $soldier]);
        ACLFactory::createOne([
            'entity' => Operation::class,
            'entityId' => (string)$visibleOperation->getId(),
            'permission' => 'view_missions',
        ]);
        $this->client->loginUser(UserFactory::createOne());

        $this->client->request('GET', '/milhq/soldier/' . $soldier->getId());

        self::assertResponseIsSuccessful();
        self::assertSelectorCount(3, '#combat-record tbody tr');
        self::assertSelectorCount(1, '#combat-record a[aria-label="View Mission"]');
        self::assertSelectorExists("#combat-record a[href='/milhq/missions/{$visibleMission->getId()}']");
    }

    public function testQualificationRecordLinksToCourseClassWhenAllowed(): void
    {
        $soldier = SoldierFactory::createOne();
        $visibleCourse = CourseFactory::createOne();
        $visibleClass = CourseClassFactory::createOne(['course' => $visibleCourse, 'result' => true]);
        $hiddenClass = CourseClassFactory::createOne(['result' => true]);
        foreach ([$visibleClass, $hiddenClass, null] as $class) {
            QualificationRecordFactory::createOne([
                'soldier' => $soldier,
                'qualification' => MilsimStory::qualificationCLS(),
                'courseClass' => $class,
            ]);
        }
        ACLFactory::createOne([
            'entity' => Course::class,
            'entityId' => (string)$visibleCourse->getId(),
            'permission' => 'view_classes',
        ]);
        $this->client->loginUser(UserFactory::createOne());

        $this->client->request('GET', '/milhq/soldier/' . $soldier->getId());

        self::assertResponseIsSuccessful();
        self::assertSelectorCount(3, '#qualification-record tbody tr');
        self::assertSelectorCount(1, '#qualification-record a[aria-label="View Class"]');
        self::assertSelectorExists("#qualification-record a[href='/milhq/courses/class/{$visibleClass->getId()}']");
    }

    public function testQualificationRecordDisplaysTier(): void
    {
        $soldier = SoldierFactory::createOne();

        $qualification = QualificationFactory::createOne([
            'name' => 'Marksmanship',
            'image' => 'qualifications/marksmanship.png',
        ]);
        $tierWithImage = QualificationTierFactory::createOne([
            'name' => 'Expert',
            'parent' => $qualification,
            'image' => 'qualifications/marksmanship-expert.png',
        ]);
        $tierWithoutImage = QualificationTierFactory::createOne([
            'name' => 'Novice',
            'parent' => $qualification,
        ]);

        QualificationRecordFactory::createOne([
            'soldier' => $soldier,
            'qualification' => $qualification,
            'tier' => $tierWithImage,
        ]);
        QualificationRecordFactory::createOne([
            'soldier' => $soldier,
            'qualification' => $qualification,
            'tier' => $tierWithoutImage,
        ]);
        QualificationRecordFactory::createOne([
            'soldier' => $soldier,
            'qualification' => MilsimStory::qualificationCLS(),
            'tier' => null,
        ]);

        $c = $this->client->request('GET', '/milhq/soldier/' . $soldier->getId());
        self::assertResponseIsSuccessful();

        $records = $c->filter('#qualification-record');
        self::assertStringContainsString('Marksmanship: Expert', $records->text());
        self::assertStringContainsString('Marksmanship: Novice', $records->text());
        self::assertStringContainsString('Combat Life Saver', $records->text());
        self::assertStringNotContainsString('Combat Life Saver:', $records->text());

        $html = $records->html();
        self::assertStringContainsString('marksmanship-expert.png', $html);
        self::assertStringContainsString('marksmanship.png', $html);
    }

    public function testAwardRecordDisplaysTier(): void
    {
        $soldier = SoldierFactory::createOne();

        $award = AwardFactory::createOne([
            'name' => 'Marksmanship Badge',
            'image' => 'awards/marksmanship.png',
        ]);
        $tierWithImage = AwardTierFactory::createOne([
            'name' => 'Expert',
            'parent' => $award,
            'image' => 'awards/marksmanship-expert.png',
        ]);
        $tierWithoutImage = AwardTierFactory::createOne([
            'name' => 'Novice',
            'parent' => $award,
        ]);

        AwardRecordFactory::createOne([
            'soldier' => $soldier,
            'award' => $award,
            'tier' => $tierWithImage,
        ]);
        AwardRecordFactory::createOne([
            'soldier' => $soldier,
            'award' => $award,
            'tier' => $tierWithoutImage,
        ]);
        AwardRecordFactory::createOne([
            'soldier' => $soldier,
            'award' => AwardFactory::createOne(['name' => 'Good Conduct Medal']),
            'tier' => null,
        ]);

        $c = $this->client->request('GET', '/milhq/soldier/' . $soldier->getId());
        self::assertResponseIsSuccessful();

        $records = $c->filter('#award-record');
        self::assertStringContainsString('Marksmanship Badge: Expert', $records->text());
        self::assertStringContainsString('Marksmanship Badge: Novice', $records->text());
        self::assertStringContainsString('Good Conduct Medal', $records->text());
        self::assertStringNotContainsString('Good Conduct Medal:', $records->text());

        $html = $records->html();
        self::assertStringContainsString('marksmanship-expert.png', $html);
        self::assertStringContainsString('marksmanship.png', $html);
    }

    public function testQualificationRecordSearchIncludesTierName(): void
    {
        $soldier = SoldierFactory::createOne();

        QualificationRecordFactory::createOne([
            'soldier' => $soldier,
            'qualification' => MilsimStory::qualificationLandNav(),
            'tier' => MilsimStory::qualificationLandNavExpert(),
        ]);
        QualificationRecordFactory::createOne([
            'soldier' => $soldier,
            'qualification' => MilsimStory::qualificationCLS(),
            'tier' => null,
        ]);

        $component = $this->createLiveComponent('Milhq\\QualificationRecordTable', [
            'soldier' => $soldier,
        ]);

        $html = $component->render()->crawler()->html();
        self::assertStringContainsString('Land Navigation', $html);
        self::assertStringContainsString('Combat Life Saver', $html);

        $component->set('query', 'Expert');
        $html = $component->render()->crawler()->html();
        self::assertStringContainsString('Land Navigation', $html);
        self::assertStringNotContainsString('Combat Life Saver', $html);
    }

    public function testProfileAwardGalleryShowsHighestTierImageForAutoAdvance(): void
    {
        $soldier = SoldierFactory::createOne();

        $autoAward = AwardFactory::createOne([
            'name' => 'Marksmanship Badge',
            'image' => 'awards/marksmanship.png',
            'autoAdvanceTiers' => true,
        ]);
        $bronze = AwardTierFactory::createOne([
            'name' => 'Bronze',
            'parent' => $autoAward,
            'image' => 'awards/marksmanship-bronze.png',
            'position' => 30,
        ]);
        $gold = AwardTierFactory::createOne([
            'name' => 'Gold',
            'parent' => $autoAward,
            'image' => 'awards/marksmanship-gold.png',
            'position' => 10,
        ]);

        AwardRecordFactory::createOne(['soldier' => $soldier, 'award' => $autoAward, 'tier' => $bronze]);
        AwardRecordFactory::createOne(['soldier' => $soldier, 'award' => $autoAward, 'tier' => $gold]);

        $manualAward = AwardFactory::createOne([
            'name' => 'Good Conduct Medal',
            'image' => 'awards/good-conduct.png',
        ]);
        AwardRecordFactory::createOne(['soldier' => $soldier, 'award' => $manualAward, 'tier' => null]);
        AwardRecordFactory::createOne(['soldier' => $soldier, 'award' => $manualAward, 'tier' => null]);

        $c = $this->client->request('GET', '/milhq/soldier/' . $soldier->getId());
        self::assertResponseIsSuccessful();

        $gallery = $c->filter('#assignment');
        $html = $gallery->html();

        self::assertStringContainsString('marksmanship-gold.png', $html);
        self::assertStringNotContainsString('marksmanship.png"', $html);
        self::assertStringNotContainsString('marksmanship-bronze.png', $html);

        self::assertStringContainsString('good-conduct.png', $html);
        self::assertStringContainsString('x2', $html);
    }

    public function testProfileAwardGalleryIsSortedByGroupThenAward(): void
    {
        $soldier = SoldierFactory::createOne();
        $groupLast = persist(AwardGroup::class, ['title' => 'Group Zulu', 'position' => 20]);
        $groupFirst = persist(AwardGroup::class, ['title' => 'Group Alpha', 'position' => 10]);

        $awards = [
            ['Ungrouped Award', null, 0],
            ['Zulu First', $groupLast, 1],
            ['Alpha Second', $groupFirst, 2],
            ['Alpha First', $groupFirst, 1],
        ];
        foreach ($awards as [$name, $group, $position]) {
            AwardRecordFactory::createOne([
                'soldier' => $soldier,
                'award' => AwardFactory::createOne([
                    'name' => $name,
                    'group' => $group,
                    'position' => $position,
                    'image' => 'awards/ribbon.png',
                ]),
            ]);
        }

        $c = $this->client->request('GET', '/milhq/soldier/' . $soldier->getId());
        self::assertResponseIsSuccessful();

        self::assertSame(
            ['Alpha First', 'Alpha Second', 'Zulu First', 'Ungrouped Award'],
            $c->filter('#assignment [title]')->each(static fn ($node) => $node->attr('title')),
        );
    }

    public function testAwardRecordSearchIncludesTierName(): void
    {
        $soldier = SoldierFactory::createOne();

        $award = AwardFactory::createOne(['name' => 'Marksmanship Badge']);
        $tier = AwardTierFactory::createOne(['name' => 'Expert', 'parent' => $award]);

        AwardRecordFactory::createOne([
            'soldier' => $soldier,
            'award' => $award,
            'tier' => $tier,
        ]);
        AwardRecordFactory::createOne([
            'soldier' => $soldier,
            'award' => AwardFactory::createOne(['name' => 'Good Conduct Medal']),
            'tier' => null,
        ]);

        $component = $this->createLiveComponent('Milhq\\AwardRecordTable', [
            'soldier' => $soldier,
        ]);

        $html = $component->render()->crawler()->html();
        self::assertStringContainsString('Marksmanship Badge', $html);
        self::assertStringContainsString('Good Conduct Medal', $html);

        $component->set('query', 'Expert');
        $html = $component->render()->crawler()->html();
        self::assertStringContainsString('Marksmanship Badge', $html);
        self::assertStringNotContainsString('Good Conduct Medal', $html);
    }

    public function testSecondaryAssignmentsAreSortedByUnitAndPosition(): void
    {
        $soldier = SoldierFactory::createOne();

        $unitLast = UnitFactory::createOne(['name' => 'Unit Zulu', 'position' => 20]);
        $unitFirst = UnitFactory::createOne(['name' => 'Unit Alpha', 'position' => 10]);
        $positionLast = PositionFactory::createOne(['name' => 'Position Zulu', 'position' => 20]);
        $positionFirst = PositionFactory::createOne(['name' => 'Position Alpha', 'position' => 10]);

        $assignments = [
            [$unitLast, $positionLast],
            [$unitFirst, $positionLast],
            [$unitLast, $positionFirst],
            [$unitFirst, $positionFirst],
        ];
        foreach ($assignments as [$unit, $position]) {
            AssignmentRecordFactory::createOne([
                'soldier' => $soldier,
                'type' => AssignmentRecord::TYPE_SECONDARY,
                'unit' => $unit,
                'position' => $position,
            ]);
        }

        $c = $this->client->request('GET', '/milhq/soldier/' . $soldier->getId());
        self::assertResponseIsSuccessful();

        $text = $c->filter('#assignment')->text();
        self::assertMatchesRegularExpression(
            '/Unit Alpha.*Position Alpha.*Position Zulu.*Unit Zulu.*Position Alpha.*Position Zulu/',
            $text,
        );
    }
}
