<?php

declare(strict_types=1);

namespace PluginTests\Tests\Unit\EventSubscriber;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Forumify\Milhq\Entity\Record\AssignmentRecord;
use PluginTests\Tests\Factories\Milhq\SoldierFactory;
use PluginTests\Tests\Factories\Stories\MilsimStory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

class AssignmentUpdateUserListenerTest extends KernelTestCase
{
    use Factories;

    public function testPrePersist(): void
    {
        MilsimStory::load();

        $targetSoldier = SoldierFactory::createOne();

        $record = new AssignmentRecord();
        $record->setSoldier($targetSoldier);
        $record->setType('primary');
        $record->setStatus(MilsimStory::statusActiveDuty());
        $record->setUnit(MilsimStory::unitFirstSquad());
        $record->setPosition(MilsimStory::positionRiflemanAT());
        $record->setSpecialty(MilsimStory::specialtyInfantry());

        $em = self::getContainer()->get(EntityManagerInterface::class);
        $em->persist($record);
        $em->flush();

        self::assertNotNull($targetSoldier->getStatus());
        self::assertNotNull($targetSoldier->getUnit());
        self::assertNotNull($targetSoldier->getPosition());
        self::assertNotNull($targetSoldier->getSpecialty());
    }

    public function testPreRemove(): void
    {
        MilsimStory::load();

        $targetSoldier = SoldierFactory::createOne();

        // First we assign to 1st squad
        $record = new AssignmentRecord();
        $record->setCreatedAt(new DateTime('yesterday'));
        $record->setSoldier($targetSoldier);
        $record->setType('primary');
        $record->setUnit(MilsimStory::unitFirstSquad());

        $em = self::getContainer()->get(EntityManagerInterface::class);
        $em->persist($record);

        self::assertEquals(MilsimStory::unitFirstSquad()->getId(), $targetSoldier->getUnit()->getId());

        // Then we assign to second squad
        $record = new AssignmentRecord();
        $record->setSoldier($targetSoldier);
        $record->setType('primary');
        $record->setUnit(MilsimStory::unitSecondSquad());

        $em->persist($record);
        $em->flush();

        self::assertEquals(MilsimStory::unitSecondSquad()->getId(), $targetSoldier->getUnit()->getId());

        // Then we remove the last assignment record, which should put the user back in 1st squad
        $em->remove($record);
        $em->flush();

        self::assertEquals(MilsimStory::unitFirstSquad()->getId(), $targetSoldier->getUnit()->getId());
    }
}
