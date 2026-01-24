<?php

declare(strict_types=1);

namespace PluginTests\Application;

use Forumify\Milhq\Entity\Record\AssignmentRecord;
use Forumify\Milhq\Entity\Record\RankRecord;
use PluginTests\Factories\Milhq\SoldierFactory;
use PluginTests\Factories\Stories\MilsimStory;

class DischargeTest extends MilhqWebTestCase
{
    public function testDischargeClearAll(): void
    {
        $users = MilsimStory::firstSquad();
        $user = $users[array_rand($users)];

        $this->client->request('GET', "/admin/milhq/users/{$user->getId()}/discharge");
        self::assertResponseIsSuccessful();

        $this->client->submitForm('Save', [
            'discharge[type]' => 'General Discharge',
            'discharge[reason]' => 'Bye bye!',
            'discharge[rank]' => '',
            'discharge[unit]' => '',
            'discharge[position]' => '',
            'discharge[status]' => '',
        ]);

        $user = SoldierFactory::find($user->getId());
        self::assertNull($user->getRank());
        self::assertNull($user->getUnit());
        self::assertNull($user->getPosition());
        self::assertNull($user->getStatus());
        self::assertNull($user->getSpecialty());

        self::assertEmpty($user->getAssignmentRecords());

        self::assertCount(1, $user->getServiceRecords());
        self::assertEquals('General Discharge: Bye bye!', $user->getServiceRecords()->first()->getText());

        self::assertEmpty($user->getRankRecords());
    }

    public function testDischargeNewAssignment(): void
    {
        $users = MilsimStory::firstSquad();
        $user = $users[array_rand($users)];

        $this->client->request('GET', "/admin/milhq/users/{$user->getId()}/discharge");
        self::assertResponseIsSuccessful();

        $rank = MilsimStory::rankPVT()->getId();
        $unit = MilsimStory::unitCivilian()->getId();
        $position = MilsimStory::positionCivilian()->getId();
        $status = MilsimStory::statusCivilian()->getId();

        $this->client->submitForm('Save', [
            'discharge[type]' => 'General Discharge',
            'discharge[reason]' => 'Bye bye!',
            'discharge[rank]' => $rank,
            'discharge[unit]' => $unit,
            'discharge[position]' => $position,
            'discharge[status]' => $status,
        ]);

        $user = SoldierFactory::find($user->getId());
        self::assertEquals($rank, $user->getRank()->getId());
        self::assertEquals($unit, $user->getUnit()->getId());
        self::assertEquals($position, $user->getPosition()->getId());
        self::assertEquals($status, $user->getStatus()->getId());
        self::assertNull($user->getSpecialty());

        self::assertCount(1, $user->getAssignmentRecords());
        /** @var AssignmentRecord $assignmentRecord */
        $assignmentRecord = $user->getAssignmentRecords()->first();
        self::assertEquals($status, $assignmentRecord->getStatus()->getId());
        self::assertEquals($unit, $assignmentRecord->getUnit()->getId());
        self::assertEquals($position, $assignmentRecord->getPosition()->getId());

        self::assertCount(1, $user->getServiceRecords());
        self::assertEquals('General Discharge: Bye bye!', $user->getServiceRecords()->first()->getText());

        self::assertCount(1, $user->getRankRecords());
        /** @var RankRecord $rankRecord */
        $rankRecord = $user->getRankRecords()->first();
        self::assertEquals('demotion', $rankRecord->getType());
        self::assertEquals($rank, $rankRecord->getRank()->getId());
    }
}
