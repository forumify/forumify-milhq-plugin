<?php

declare(strict_types=1);

namespace PluginTests\Tests\Application;

use DateInterval;
use DateTimeImmutable;
use Forumify\Milhq\Repository\AwardRecordRepository;
use Forumify\Milhq\Repository\CourseClassRepository;
use Forumify\Milhq\Repository\CourseInstructorRepository;
use Forumify\Milhq\Repository\QualificationRecordRepository;
use Forumify\Milhq\Repository\ServiceRecordRepository;
use PluginTests\Tests\Application\MilhqWebTestCase;
use PluginTests\Tests\Factories\Milhq\AwardFactory;
use PluginTests\Tests\Factories\Milhq\AwardTierFactory;
use PluginTests\Tests\Factories\Milhq\Record\QualificationRecordFactory;
use PluginTests\Tests\Factories\Milhq\SoldierFactory;
use PluginTests\Tests\Factories\Stories\MilsimStory;
use PluginTests\Tests\Traits\SessionTrait;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

class CoursesTest extends MilhqWebTestCase
{
    use InteractsWithLiveComponents;
    use SessionTrait;

    public function testCourseToClassReport(): void
    {
        $soldier = SoldierFactory::createOne([
            'rank' => MilsimStory::rankPFC(),
            'user' => $this->user,
        ]);

        $marksmanship = AwardFactory::createOne(['name' => 'Marksmanship Badge']);
        $marksmanBronze = AwardTierFactory::createOne(['name' => 'Bronze', 'parent' => $marksmanship, 'position' => 0]);
        AwardTierFactory::createOne(['name' => 'Silver', 'parent' => $marksmanship, 'position' => 1]);

        $serviceRibbon = AwardFactory::createOne(['name' => 'Service Ribbon', 'autoAdvanceTiers' => true]);
        AwardTierFactory::createOne(['name' => 'First', 'parent' => $serviceRibbon, 'position' => 0]);
        AwardTierFactory::createOne(['name' => 'Second', 'parent' => $serviceRibbon, 'position' => 1]);

        $c = $this->client->request('GET', '/admin/milhq/courses');
        $this->client->click($c->filter('a[aria-label="New Course"]')->link());

        $this->client->submitForm('Save', [
            'course[title]' => 'Combat Life Saver Course',
            'course[description]' => '<p>Learn about being a CLS!</p>',
            'course[minimumRank]' => MilsimStory::rankPFC()->getId(),
            'course[prerequisites]' => [MilsimStory::qualificationLandNav()->getId()],
            'course[qualifications]' => [
                MilsimStory::qualificationCLS()->getId(),
                MilsimStory::qualificationLandNav()->getId(),
            ],
            'course[awards]' => [
                $marksmanship->getId(),
                $serviceRibbon->getId(),
            ],
        ]);
        $this->client->submitForm('Save');

        $this->initializeSession();
        $c = $this
            ->createLiveComponent('Milhq\\CourseList', ['expanded' => false])
            ->actingAs($this->user)
            ->render()
            ->crawler()
        ;

        $link = $c->filter('.topic-link')->first();
        self::assertStringContainsString('Combat Life Saver Course', $link->siblings()->first()->innerText());

        $this->client->request('GET', $link->attr('href'));
        self::assertAnySelectorTextContains('.rich-text p', 'Learn about being a CLS!');

        $now = new DateTimeImmutable();
        $signupFrom = $now->sub(new DateInterval('PT1H'));
        $signupUntil = $now->add(new DateInterval('PT1H'));
        $start = $now->add(new DateInterval('PT2H'));
        $end = $now->add(new DateInterval('PT4H'));

        $this->client->clickLink('New Class');
        $this->client->submitForm('Save', [
            'course_class[title]' => 'CLS 001',
            'course_class[description]' => '<p>Test class.</p>',
            'course_class[signupFrom]' => $signupFrom->format('Y-m-d\TH:i:s'),
            'course_class[signupUntil]' => $signupUntil->format('Y-m-d\TH:i:s'),
            'course_class[start]' => $start->format('Y-m-d\TH:i:s'),
            'course_class[end]' => $end->format('Y-m-d\TH:i:s'),
        ]);

        $classId = $this->client->getRequest()->attributes->get('id');
        $class = self::getContainer()->get(CourseClassRepository::class)->find($classId);
        self::assertNotNull($class);

        $classComponent = $this
            ->createLiveComponent('Milhq\\CourseClassView', ['class' => $class])
            ->actingAs($this->user)
        ;
        self::assertStringContainsString('Prerequisites not met', (string)$classComponent->render());

        QualificationRecordFactory::createOne([
            'qualification' => MilsimStory::qualificationLandNav(),
            'soldier' => $soldier,
        ]);

        $render = $this
            ->createLiveComponent('Milhq\\CourseClassView', ['class' => $class])
            ->actingAs($this->user)
            ->render()
            ->toString()
        ;
        self::assertStringContainsString('Register as Student', $render);
        self::assertStringContainsString('Register as Instructor', $render);

        $render = $this
            ->createLiveComponent('Milhq\\CourseClassView', ['class' => $class])
            ->actingAs($this->user)
            ->call('toggleStudent')
            ->call('registerInstructor')
            ->render()
            ->toString()
        ;
        self::assertStringContainsString('Deregister as Student', $render);
        self::assertStringContainsString('Deregister as Instructor', $render);

        $clsId = MilsimStory::qualificationCLS()->getId();
        $landNavId = MilsimStory::qualificationLandNav()->getId();
        $landNavExpert = MilsimStory::qualificationLandNavExpert();
        $reportUrl = self::getContainer()->get('router')->generate('milhq_course_class_report', ['id' => $classId]);

        $this->client->request('GET', $reportUrl);
        self::assertResponseIsSuccessful();
        self::assertSelectorNotExists('.alert-warning');
        self::assertSelectorExists('[data-role="present"]');
        self::assertSelectorExists('[data-role-cell]');
        self::assertSelectorExists('td.d-none [data-role-cell]');
        self::assertSelectorExists('[data-role="result"]');
        self::assertSelectorExists('[data-achievement-cell]');
        self::assertCount(
            1,
            $this->client->getCrawler()->filter('[name="class_result[instructors][0][instructor]"]'),
        );
        self::assertSelectorExists('[data-controller~="forumify--forumify-milhq-plugin--course-class-report"]');
        self::assertSelectorExists('[data-forumify--forumify-milhq-plugin--course-class-report-target="students"]');
        self::assertSelectorExists('[data-forumify--forumify-milhq-plugin--course-class-report-target="instructors"]');
        self::assertSelectorExists('[data-controller~="forumify--rich-text-editor"]');
        self::assertSelectorExists('form[novalidate]');

        $this->client->request('GET', "/admin/milhq/courses/{$class->getCourse()->getId()}/new-instructor");
        $this->client->submitForm('Save', [
            'course_instructor[title]' => 'Safety Officer',
            'course_instructor[description]' => 'Oversees range safety.',
        ]);
        $safetyOfficer = self::getContainer()->get(CourseInstructorRepository::class)->findOneBy(['title' => 'Safety Officer']);
        self::assertNotNull($safetyOfficer);

        $this->client->request('GET', $reportUrl);
        self::assertSelectorExists('td:not(.d-none) [data-role-cell]');

        $this->client->submitForm('Save', [
            'class_result[instructors][0][present]' => true,
            'class_result[instructors][0][instructor]' => $safetyOfficer->getId(),
            'class_result[students][0][result]' => 'passed',
            "class_result[students][0][qualification_{$clsId}]" => true,
            "class_result[students][0][qualification_{$landNavId}]" => (string)$landNavExpert->getId(),
            "class_result[students][0][award_{$marksmanship->getId()}]" => (string)$marksmanBronze->getId(),
            "class_result[students][0][award_{$serviceRibbon->getId()}]" => true,
        ]);

        self::assertResponseIsSuccessful();

        $this->client->request('GET', $reportUrl);
        self::assertSelectorExists('.alert-warning');
        self::assertSelectorExists(
            "select[name=\"class_result[students][0][result]\"] option[value=\"passed\"][selected]",
        );
        self::assertSelectorExists(
            "input[name=\"class_result[students][0][qualification_{$clsId}]\"][checked]",
        );
        self::assertSelectorExists(
            "select[name=\"class_result[students][0][qualification_{$landNavId}]\"] option[value=\"{$landNavExpert->getId()}\"][selected]",
        );
        self::assertSelectorExists(
            "select[name=\"class_result[students][0][award_{$marksmanship->getId()}]\"] option[value=\"{$marksmanBronze->getId()}\"][selected]",
        );
        self::assertSelectorExists(
            "input[name=\"class_result[students][0][award_{$serviceRibbon->getId()}]\"][checked]",
        );

        $qualificationRecords = self::getContainer()->get(QualificationRecordRepository::class)->findBy(['soldier' => $soldier->getId()]);
        $realQualifications = array_map(fn ($record) => $record->getQualification()->getName(), $qualificationRecords);
        foreach (['Land Navigation', 'Combat Life Saver'] as $expected) {
            self::assertContains($expected, $realQualifications);
        }

        $tieredLandNav = array_filter(
            $qualificationRecords,
            fn ($record) => $record->getQualification()->getName() === 'Land Navigation' && $record->getTier() !== null,
        );
        self::assertCount(1, $tieredLandNav);
        self::assertSame('Expert', reset($tieredLandNav)->getTier()->getNameForAudit());

        $awardRecords = self::getContainer()->get(AwardRecordRepository::class)->findBy(['soldier' => $soldier->getId()]);
        $awardsByName = [];
        foreach ($awardRecords as $record) {
            $awardsByName[$record->getAward()->getName()] = $record;
        }

        self::assertArrayHasKey('Marksmanship Badge', $awardsByName);
        self::assertSame('Bronze', $awardsByName['Marksmanship Badge']->getTier()?->getNameForAudit());

        self::assertArrayHasKey('Service Ribbon', $awardsByName);
        self::assertNotNull($awardsByName['Service Ribbon']->getTier());

        $serviceRecords = self::getContainer()->get(ServiceRecordRepository::class)->findBy(['soldier' => $soldier->getId()]);
        $realServiceRecords = array_map(fn ($record) => $record->getText(), $serviceRecords);
        foreach (['Attended CLS 001 as Safety Officer', 'Graduated CLS 001'] as $expected) {
            self::assertContains($expected, $realServiceRecords);
        }
    }
}
