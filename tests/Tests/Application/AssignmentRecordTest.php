<?php

declare(strict_types=1);

namespace PluginTests\Application;

use Forumify\Milhq\Entity\Soldier;
use Forumify\Milhq\Repository\SoldierRepository;
use PluginTests\Factories\Milhq\SoldierFactory;
use PluginTests\Factories\Stories\MilsimStory;

class AssignmentRecordTest extends MilhqWebTestCase
{
    public function testCreatePrimaryAssignmentRecord(): void
    {
        $targetUser = SoldierFactory::createOne();

        $c = $this->client->request('GET', '/admin/milhq/records/assignment');
        $newRecordLink = $c->filter('a[aria-label="New assignment record"]')->link();
        $this->client->click($newRecordLink);

        // phpcs:ignore
        $this->client->submitForm('Save', [
            'record[users]' => [$targetUser->getId()],
            'record[type]' => 'primary',
            'record[status]' => MilsimStory::statusActiveDuty()->getId(),
            'record[specialty]' => MilsimStory::specialtyInfantry()->getId(),
            'record[unit]' => MilsimStory::unitFirstSquad()->getId(),
            'record[position]' => MilsimStory::positionRiflemanAT()->getId(),
            'record[text]' => 'Initial assignment',
        ]);

        self::assertResponseIsSuccessful();

        /** @var Soldier $soldier */
        $soldier = self::getContainer()->get(SoldierRepository::class)->find($targetUser->getId());
        self::assertEquals('Active Duty', $soldier->getStatus()->getName());
        self::assertEquals('11B', $soldier->getSpecialty()->getAbbreviation());
        self::assertEquals('First Squad', $soldier->getUnit()->getName());
        self::assertEquals('Rifleman AT', $soldier->getPosition()->getName());
    }

    public function testCreateSecondaryAssignmentRecord(): void
    {
        $targetUser = SoldierFactory::createOne();

        $c = $this->client->request('GET', '/admin/milhq/records/assignment');
        $newRecordLink = $c->filter('a[aria-label="New assignment record"]')->link();
        $this->client->click($newRecordLink);

        $this->client->submitForm('Save', [
            'record[users]' => [$targetUser->getId()],
            'record[type]' => 'secondary',
            'record[status]' => MilsimStory::statusActiveDuty()->getId(),
            'record[specialty]' => MilsimStory::specialtyInfantry()->getId(),
            'record[unit]' => MilsimStory::unitFirstSquad()->getId(),
            'record[position]' => MilsimStory::positionRiflemanAT()->getId(),
            'record[text]' => 'Initial assignment',
        ]);

        self::assertResponseIsSuccessful();

        /** @var Soldier $soldier */
        $soldier = self::getContainer()->get(SoldierRepository::class)->find($targetUser->getId());
        self::assertNull($soldier->getStatus());
        self::assertNull($soldier->getPosition());
        self::assertNull($soldier->getSpecialty());
        self::assertNull($soldier->getUnit());
    }
}
