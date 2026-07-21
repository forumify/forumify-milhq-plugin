<?php

declare(strict_types=1);

namespace PluginTests\Tests\Application;

use Forumify\Milhq\Entity\Record\AwardRecord;
use Forumify\Milhq\Repository\AwardRecordRepository;
use PluginTests\Tests\Factories\Milhq\AwardFactory;
use PluginTests\Tests\Factories\Milhq\AwardTierFactory;
use PluginTests\Tests\Factories\Milhq\Record\AwardRecordFactory;
use PluginTests\Tests\Factories\Milhq\SoldierFactory;

class AwardRecordTest extends MilhqWebTestCase
{
    public function testCreateAwardRecordWithTier(): void
    {
        $targetUser = SoldierFactory::createOne();
        $award = AwardFactory::createOne(['name' => 'Marksmanship Badge']);
        $tier = AwardTierFactory::createOne(['name' => 'Expert', 'parent' => $award, 'position' => 10]);

        $this->openRecordForm();

        // phpcs:ignore
        $this->client->submitForm('Save', [
            'record[soldiers]' => [$targetUser->getId()],
            'record[award]' => $award->getId(),
            'record[tier]' => $tier->getId(),
            'record[text]' => 'Earned the expert badge',
        ]);

        self::assertResponseIsSuccessful();

        $record = $this->findRecord($targetUser->getId());
        self::assertNotNull($record);
        self::assertEquals('Marksmanship Badge', $record->getAward()->getName());
        self::assertEquals('Expert', $record->getTier()?->name);
    }

    public function testCreateAwardRecordWithoutTiers(): void
    {
        $targetUser = SoldierFactory::createOne();
        $award = AwardFactory::createOne(['name' => 'Good Conduct Medal']);

        $this->openRecordForm();

        // phpcs:ignore
        $this->client->submitForm('Save', [
            'record[soldiers]' => [$targetUser->getId()],
            'record[award]' => $award->getId(),
            'record[text]' => 'Earned it',
        ]);

        self::assertResponseIsSuccessful();

        $record = $this->findRecord($targetUser->getId());
        self::assertNotNull($record);
        self::assertNull($record->getTier());
    }

    public function testTieredAwardRequiresATierWhenNotAutoAdvancing(): void
    {
        $targetUser = SoldierFactory::createOne();
        $award = AwardFactory::createOne(['name' => 'Marksmanship Badge']);
        AwardTierFactory::createOne(['name' => 'Expert', 'parent' => $award, 'position' => 10]);

        $this->openRecordForm();

        // phpcs:ignore
        $this->client->submitForm('Save', [
            'record[soldiers]' => [$targetUser->getId()],
            'record[award]' => $award->getId(),
            'record[text]' => 'Earned the badge',
        ]);

        self::assertNull($this->findRecord($targetUser->getId()));
    }

    public function testTierMustBelongToAward(): void
    {
        $targetUser = SoldierFactory::createOne();
        $awardA = AwardFactory::createOne(['name' => 'Award A']);
        $awardB = AwardFactory::createOne(['name' => 'Award B']);
        $tierB = AwardTierFactory::createOne(['name' => 'Tier B', 'parent' => $awardB, 'position' => 10]);

        $this->openRecordForm();

        // phpcs:ignore
        $this->client->submitForm('Save', [
            'record[soldiers]' => [$targetUser->getId()],
            'record[award]' => $awardA->getId(),
            'record[tier]' => $tierB->getId(),
            'record[text]' => 'Mismatched tier',
        ]);

        self::assertNull($this->findRecord($targetUser->getId()));
    }

    public function testAutoAdvanceStartsAtLowestTierWhenNoPreviousRecord(): void
    {
        $targetUser = SoldierFactory::createOne();
        $award = AwardFactory::createOne(['name' => 'Marksmanship Badge', 'autoAdvanceTiers' => true]);
        AwardTierFactory::createOne(['name' => 'Gold', 'parent' => $award, 'position' => 10]);
        AwardTierFactory::createOne(['name' => 'Silver', 'parent' => $award, 'position' => 20]);
        AwardTierFactory::createOne(['name' => 'Bronze', 'parent' => $award, 'position' => 30]);

        $this->openRecordForm();

        // phpcs:ignore
        $this->client->submitForm('Save', [
            'record[soldiers]' => [$targetUser->getId()],
            'record[award]' => $award->getId(),
            'record[text]' => 'First award',
        ]);

        self::assertResponseIsSuccessful();

        $record = $this->findRecord($targetUser->getId());
        self::assertNotNull($record);
        self::assertEquals('Bronze', $record->getTier()?->name);
    }

    public function testAutoAdvanceMovesToNextTierFromPrevious(): void
    {
        $targetUser = SoldierFactory::createOne();
        $award = AwardFactory::createOne(['name' => 'Marksmanship Badge', 'autoAdvanceTiers' => true]);
        AwardTierFactory::createOne(['name' => 'Gold', 'parent' => $award, 'position' => 10]);
        AwardTierFactory::createOne(['name' => 'Silver', 'parent' => $award, 'position' => 20]);
        $bronze = AwardTierFactory::createOne(['name' => 'Bronze', 'parent' => $award, 'position' => 30]);

        AwardRecordFactory::createOne([
            'soldier' => $targetUser,
            'award' => $award,
            'tier' => $bronze,
        ]);

        $this->openRecordForm();

        // phpcs:ignore
        $this->client->submitForm('Save', [
            'record[soldiers]' => [$targetUser->getId()],
            'record[award]' => $award->getId(),
            'record[text]' => 'Second award',
        ]);

        self::assertResponseIsSuccessful();

        $records = self::getContainer()
            ->get(AwardRecordRepository::class)
            ->findBy(['soldier' => $targetUser->getId()]);
        self::assertCount(2, $records);

        $newest = $this->findRecord($targetUser->getId());
        self::assertEquals('Silver', $newest?->getTier()?->name);
    }

    public function testAutoAdvanceCapsAtHighestTier(): void
    {
        $targetUser = SoldierFactory::createOne();
        $award = AwardFactory::createOne(['name' => 'Marksmanship Badge', 'autoAdvanceTiers' => true]);
        $gold = AwardTierFactory::createOne(['name' => 'Gold', 'parent' => $award, 'position' => 10]);
        AwardTierFactory::createOne(['name' => 'Silver', 'parent' => $award, 'position' => 20]);
        AwardTierFactory::createOne(['name' => 'Bronze', 'parent' => $award, 'position' => 30]);

        AwardRecordFactory::createOne([
            'soldier' => $targetUser,
            'award' => $award,
            'tier' => $gold,
        ]);

        $this->openRecordForm();

        // phpcs:ignore
        $this->client->submitForm('Save', [
            'record[soldiers]' => [$targetUser->getId()],
            'record[award]' => $award->getId(),
            'record[text]' => 'Third award',
        ]);

        self::assertResponseIsSuccessful();

        $newest = $this->findRecord($targetUser->getId());
        self::assertEquals('Gold', $newest?->getTier()?->name);
    }

    public function testAutoAdvanceIgnoresSubmittedTier(): void
    {
        $targetUser = SoldierFactory::createOne();
        $award = AwardFactory::createOne(['name' => 'Marksmanship Badge', 'autoAdvanceTiers' => true]);
        AwardTierFactory::createOne(['name' => 'Gold', 'parent' => $award, 'position' => 10]);
        $silver = AwardTierFactory::createOne(['name' => 'Silver', 'parent' => $award, 'position' => 20]);
        AwardTierFactory::createOne(['name' => 'Bronze', 'parent' => $award, 'position' => 30]);

        $this->openRecordForm();

        // phpcs:ignore
        $this->client->submitForm('Save', [
            'record[soldiers]' => [$targetUser->getId()],
            'record[award]' => $award->getId(),
            'record[tier]' => $silver->getId(),
            'record[text]' => 'First award',
        ]);

        self::assertResponseIsSuccessful();

        $record = $this->findRecord($targetUser->getId());
        self::assertEquals('Bronze', $record?->getTier()?->name);
    }

    private function openRecordForm(): void
    {
        $c = $this->client->request('GET', '/admin/milhq/records/award');
        $this->client->click($c->filter('a[aria-label="New award record"]')->link());
    }

    private function findRecord(int $soldierId): ?AwardRecord
    {
        return self::getContainer()
            ->get(AwardRecordRepository::class)
            ->findOneBy(['soldier' => $soldierId], ['id' => 'DESC']);
    }
}
