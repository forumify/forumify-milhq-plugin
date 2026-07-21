<?php

declare(strict_types=1);

namespace PluginTests\Tests\Application;

use Forumify\Milhq\Entity\Record\QualificationRecord;
use Forumify\Milhq\Repository\QualificationRecordRepository;
use PluginTests\Tests\Factories\Milhq\SoldierFactory;
use PluginTests\Tests\Factories\Stories\MilsimStory;

class QualificationRecordTest extends MilhqWebTestCase
{
    public function testCreateQualificationRecordWithTier(): void
    {
        $targetUser = SoldierFactory::createOne();
        $this->openRecordForm();

        // phpcs:ignore
        $this->client->submitForm('Save', [
            'record[soldiers]' => [$targetUser->getId()],
            'record[qualification]' => MilsimStory::qualificationLandNav()->getId(),
            'record[tier]' => MilsimStory::qualificationLandNavExpert()->getId(),
            'record[text]' => 'Passed the expert course',
        ]);

        self::assertResponseIsSuccessful();

        $record = $this->findRecord($targetUser->getId());
        self::assertNotNull($record);
        self::assertEquals('Land Navigation', $record->getQualification()->getName());
        self::assertEquals('Expert', $record->getTier()?->name);
    }

    public function testCreateQualificationRecordWithoutTier(): void
    {
        $targetUser = SoldierFactory::createOne();
        $this->openRecordForm();

        // phpcs:ignore
        $this->client->submitForm('Save', [
            'record[soldiers]' => [$targetUser->getId()],
            'record[qualification]' => MilsimStory::qualificationCLS()->getId(),
            'record[text]' => 'Passed the course',
        ]);

        self::assertResponseIsSuccessful();

        $record = $this->findRecord($targetUser->getId());
        self::assertNotNull($record);
        self::assertEquals('Combat Life Saver', $record->getQualification()->getName());
        self::assertNull($record->getTier());
    }

    public function testTieredQualificationRequiresATier(): void
    {
        $targetUser = SoldierFactory::createOne();
        $this->openRecordForm();

        // phpcs:ignore
        $this->client->submitForm('Save', [
            'record[soldiers]' => [$targetUser->getId()],
            'record[qualification]' => MilsimStory::qualificationLandNav()->getId(),
            'record[text]' => 'Passed the course',
        ]);

        self::assertNull($this->findRecord($targetUser->getId()));
    }

    public function testTierMustBelongToQualification(): void
    {
        $targetUser = SoldierFactory::createOne();
        $this->openRecordForm();

        // phpcs:ignore
        $this->client->submitForm('Save', [
            'record[soldiers]' => [$targetUser->getId()],
            'record[qualification]' => MilsimStory::qualificationCLS()->getId(),
            'record[tier]' => MilsimStory::qualificationLandNavExpert()->getId(),
            'record[text]' => 'Passed the course',
        ]);

        self::assertNull($this->findRecord($targetUser->getId()));
    }

    private function openRecordForm(): void
    {
        $c = $this->client->request('GET', '/admin/milhq/records/qualification');
        $this->client->click($c->filter('a[aria-label="New qualification record"]')->link());
    }

    private function findRecord(int $soldierId): ?QualificationRecord
    {
        return self::getContainer()
            ->get(QualificationRecordRepository::class)
            ->findOneBy(['soldier' => $soldierId]);
    }
}
