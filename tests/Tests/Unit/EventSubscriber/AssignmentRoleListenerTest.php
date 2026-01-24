<?php

declare(strict_types=1);

namespace PluginTests\Unit\EventSubscriber;

use Doctrine\ORM\EntityManagerInterface;
use Forumify\Core\Entity\Role;
use Forumify\Milhq\Entity\Record\AssignmentRecord;
use PluginTests\Factories\Forumify\RoleFactory;
use PluginTests\Factories\Milhq\StatusFactory;
use PluginTests\Factories\Milhq\UnitFactory;
use PluginTests\Factories\Milhq\SoldierFactory;
use PluginTests\Traits\UserTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

class AssignmentRoleListenerTest extends KernelTestCase
{
    use Factories;
    use UserTrait;

    public function testAssignmentRoleListener(): void
    {
        $status = StatusFactory::createOne([
            'name' => 'Active Duty',
            'role' => RoleFactory::createOne(['title' => 'Active Duty']),
        ]);

        $targetUser = $this->createUser();
        $targetSoldier = SoldierFactory::createOne(['user' => $targetUser]);

        $record = new AssignmentRecord();
        $record->setStatus($status);
        $record->setSoldier($targetSoldier);

        $em = self::getContainer()->get(EntityManagerInterface::class);
        $em->persist($record);
        $em->flush();

        self::assertCount(1, $targetUser->getRoleEntities());
        self::assertEquals('Active Duty', $targetUser->getRoleEntities()->first()->getTitle());

        $em->remove($record);
        $em->flush();

        self::assertCount(0, $targetUser->getRoleEntities());
    }

    /**
     * Primary assignments are additive instead of absolute, so old roles may not be removed.
     */
    public function testPrimaryAssignmentChanges(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $status = StatusFactory::createOne([
            'name' => 'Active Duty',
            'role' => RoleFactory::createOne(['title' => 'Active Duty']),
        ]);

        $unitA = UnitFactory::createOne([
            'name' => 'UnitA',
            'role' => RoleFactory::createOne(['title' => 'UnitA']),
        ]);

        $unitB = UnitFactory::createOne([
            'name' => 'UnitB',
            'role' => RoleFactory::createOne(['title' => 'UnitB']),
        ]);

        $unitC = UnitFactory::createOne([
            'name' => 'UnitC',
            'role' => RoleFactory::createOne(['title' => 'UnitC']),
        ]);

        $targetUser = $this->createUser();
        $targetSoldier = SoldierFactory::createOne(['user' => $targetUser]);

        // Add primary assignment to unit A and active duty
        // Add secondary assignment to unit C

        $record = new AssignmentRecord();
        $record->setStatus($status);
        $record->setUnit($unitA);
        $record->setSoldier($targetSoldier);
        $em->persist($record);
        $em->flush();

        $secondaryRecord = new AssignmentRecord();
        $secondaryRecord->setType('secondary');
        $secondaryRecord->setUnit($unitC);
        $secondaryRecord->setSoldier($targetSoldier);

        $em->persist($secondaryRecord);
        $em->flush();

        self::assertCount(3, $targetUser->getRoleEntities());
        $assignedRoles = $targetUser->getRoleEntities()->map(fn (Role $role) => $role->getTitle())->toArray();
        self::assertContains('Active Duty', $assignedRoles);
        self::assertContains('UnitA', $assignedRoles);
        self::assertNotContains('UnitB', $assignedRoles);
        self::assertContains('UnitC', $assignedRoles);

        // Add primary assignment to unit B,
        // Should keep unit C and status

        $record = new AssignmentRecord();
        $record->setUnit($unitB);
        $record->setSoldier($targetSoldier);

        $em->persist($record);
        $em->flush();

        $assignedRoles = $targetUser->getRoleEntities()->map(fn (Role $role) => $role->getTitle())->toArray();
        self::assertContains('Active Duty', $assignedRoles);
        self::assertNotContains('UnitA', $assignedRoles);
        self::assertContains('UnitB', $assignedRoles);
        self::assertContains('UnitC', $assignedRoles);

        // Add primary assignment to unit C, then remove it
        // Should remove unit B and keep unit C, since it's still in secondary assignments

        $record = new AssignmentRecord();
        $record->setUnit($unitC);
        $record->setSoldier($targetSoldier);

        $em->persist($record);
        $em->flush();
        $em->remove($record);
        $em->flush();

        $assignedRoles = $targetUser->getRoleEntities()->map(fn (Role $role) => $role->getTitle())->toArray();
        self::assertContains('Active Duty', $assignedRoles);
        self::assertNotContains('UnitA', $assignedRoles);
        self::assertNotContains('UnitB', $assignedRoles);
        self::assertContains('UnitC', $assignedRoles);

        // Remove secondary unit C
        // Should only keep active duty

        $em->remove($secondaryRecord);
        $em->flush();

        $assignedRoles = $targetUser->getRoleEntities()->map(fn (Role $role) => $role->getTitle())->toArray();
        self::assertContains('Active Duty', $assignedRoles);
        self::assertNotContains('UnitA', $assignedRoles);
        self::assertNotContains('UnitB', $assignedRoles);
        self::assertNotContains('UnitC', $assignedRoles);
    }
}
