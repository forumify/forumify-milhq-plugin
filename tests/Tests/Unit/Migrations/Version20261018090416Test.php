<?php

declare(strict_types=1);

namespace PluginTests\Tests\Unit\Migrations;

use DateTime;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Version\Version;
use Doctrine\ORM\EntityManagerInterface;
use ForumifyMilhqPluginMigrations\Version20261018090416;
use Forumify\Milhq\Entity\Enum\CourseResult;
use Forumify\Milhq\Entity\Record\QualificationRecord;
use PluginTests\Tests\Factories\Milhq\CourseClassFactory;
use PluginTests\Tests\Factories\Milhq\CourseClassStudentFactory;
use PluginTests\Tests\Factories\Milhq\QualificationFactory;
use PluginTests\Tests\Factories\Milhq\Record\QualificationRecordFactory;
use PluginTests\Tests\Factories\Milhq\Record\ServiceRecordFactory;
use PluginTests\Tests\Factories\Milhq\SoldierFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

class Version20261018090416Test extends KernelTestCase
{
    use Factories;

    public function testBackfillLinksQualificationRecordsToCourseClasses(): void
    {
        $qualification = QualificationFactory::createOne();
        $otherQualification = QualificationFactory::createOne();
        [$quick, $edited, $editedNoServiceRecord, $failed, $alreadyLinked] = SoldierFactory::createMany(5);
        $class = CourseClassFactory::createOne([
            'title' => 'CLS 001',
            'result' => true,
            'end' => new DateTime('2026-01-01 12:00:00'),
        ]);
        $otherClass = CourseClassFactory::createOne(['result' => true]);
        foreach ([$quick, $edited, $editedNoServiceRecord, $alreadyLinked] as $soldier) {
            CourseClassStudentFactory::createOne([
                'class' => $class,
                'soldier' => $soldier,
                'result' => CourseResult::Passed,
                'qualifications' => [$qualification->getId() => null],
            ]);
        }
        CourseClassStudentFactory::createOne([
            'class' => $class,
            'soldier' => $failed,
            'result' => CourseResult::Failed,
            'qualifications' => [$qualification->getId() => null],
        ]);
        $this->setClassUpdatedAt($class->getId(), '2026-01-01 13:00:00');

        $quickRecord = QualificationRecordFactory::createOne(['soldier' => $quick, 'qualification' => $qualification, 'createdAt' => new DateTime('2026-01-01 13:00:01')]);
        $otherQualificationRecord = QualificationRecordFactory::createOne(['soldier' => $quick, 'qualification' => $otherQualification, 'createdAt' => new DateTime('2026-01-01 13:00:01')]);
        $editedRecord = QualificationRecordFactory::createOne(['soldier' => $edited, 'qualification' => $qualification, 'createdAt' => new DateTime('2025-12-31 13:00:01')]);
        ServiceRecordFactory::createOne(['soldier' => $edited, 'text' => 'Graduated CLS 001', 'createdAt' => new DateTime('2025-12-31 13:00:00')]);
        $editedNoServiceRecordRecord = QualificationRecordFactory::createOne(['soldier' => $editedNoServiceRecord, 'qualification' => $qualification, 'createdAt' => new DateTime('2025-12-31 13:00:01')]);
        $failedRecord = QualificationRecordFactory::createOne(['soldier' => $failed, 'qualification' => $qualification, 'createdAt' => new DateTime('2026-01-01 13:00:01')]);
        $alreadyLinkedRecord = QualificationRecordFactory::createOne(['soldier' => $alreadyLinked, 'qualification' => $qualification, 'courseClass' => $otherClass, 'createdAt' => new DateTime('2026-01-01 13:00:01')]);

        $this->runBackfill();

        self::assertSame($class->getId(), $this->findLinkedCourseClassId($quickRecord->getId()));
        self::assertNull($this->findLinkedCourseClassId($otherQualificationRecord->getId()));
        self::assertSame($class->getId(), $this->findLinkedCourseClassId($editedRecord->getId()));
        self::assertNull($this->findLinkedCourseClassId($editedNoServiceRecordRecord->getId()));
        self::assertNull($this->findLinkedCourseClassId($failedRecord->getId()));
        self::assertSame($otherClass->getId(), $this->findLinkedCourseClassId($alreadyLinkedRecord->getId()));
    }

    private function setClassUpdatedAt(int $classId, string $updatedAt): void
    {
        self::getContainer()
            ->get(EntityManagerInterface::class)
            ->getConnection()
            ->executeStatement('UPDATE milhq_course_class SET updated_at = ? WHERE id = ?', [$updatedAt, $classId]);
    }

    private function runBackfill(): void
    {
        $migration = self::getContainer()
            ->get('doctrine.migrations.dependency_factory')
            ->getMigrationRepository()
            ->getMigration(new Version(Version20261018090416::class))
            ->getMigration();
        $migration->up(new Schema());
        $queries = $migration->getSql();

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->getConnection()->executeStatement(end($queries)->getStatement());
        $entityManager->clear();
    }

    private function findLinkedCourseClassId(int $recordId): ?int
    {
        return self::getContainer()
            ->get(EntityManagerInterface::class)
            ->find(QualificationRecord::class, $recordId)
            ?->getCourseClass()
            ?->getId();
    }
}
