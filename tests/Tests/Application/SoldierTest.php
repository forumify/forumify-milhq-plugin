<?php

declare(strict_types=1);

namespace PluginTests\Tests\Application;

use PluginTests\Tests\Factories\Milhq\AwardFactory;
use PluginTests\Tests\Factories\Milhq\AwardTierFactory;
use PluginTests\Tests\Factories\Milhq\QualificationFactory;
use PluginTests\Tests\Factories\Milhq\QualificationTierFactory;
use PluginTests\Tests\Factories\Milhq\Record\AwardRecordFactory;
use PluginTests\Tests\Factories\Milhq\Record\QualificationRecordFactory;
use PluginTests\Tests\Factories\Milhq\SoldierFactory;
use PluginTests\Tests\Factories\Stories\MilsimStory;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

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
}
