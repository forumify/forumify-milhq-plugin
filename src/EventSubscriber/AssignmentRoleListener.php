<?php

declare(strict_types=1);

namespace Forumify\Milhq\EventSubscriber;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Forumify\Core\Entity\Role;
use Forumify\Core\Entity\User;
use Forumify\Milhq\Entity\Soldier;
use Forumify\Milhq\Entity\Record\AssignmentRecord;
use Forumify\Milhq\Repository\AssignmentRecordRepository;
use Forumify\Plugin\Service\PluginVersionChecker;

#[AsEntityListener(Events::prePersist, 'prePersist', entity: AssignmentRecord::class, priority: 100)]
#[AsEntityListener(Events::preRemove, 'preRemove', entity: AssignmentRecord::class, priority: -100)]
class AssignmentRoleListener
{
    public function __construct(
        private readonly AssignmentRecordRepository $assignmentRecordRepository,
        private readonly PluginVersionChecker $pluginVersionChecker,
    ) {
    }

    public function prePersist(AssignmentRecord $addedRecord): void
    {
        if (!$this->pluginVersionChecker->isVersionInstalled('forumify-milhq-plugin', 'premium')) {
            return;
        }

        $soldier = $addedRecord->getSoldier();
        $forumUser = $soldier->getUser();
        if ($forumUser === null) {
            return;
        }

        $rolesToAdd = $this->getRoles($addedRecord);
        if ($addedRecord->getType() === AssignmentRecord::TYPE_SECONDARY) {
            $this->addRoles($forumUser, $rolesToAdd);
            return;
        }

        $rolesToRemove = [];
        if ($soldier->getStatus()?->role && $addedRecord->getStatus() !== null) {
            $rolesToRemove[] = $soldier->getStatus()->role;
        }
        if ($soldier->getUnit()?->role && $addedRecord->getUnit() !== null) {
            $rolesToRemove[] = $soldier->getUnit()->role;
        }
        if ($soldier->getPosition()?->role && $addedRecord->getPosition() !== null) {
            $rolesToRemove[] = $soldier->getPosition()->role;
        }
        if ($soldier->getSpecialty()?->role && $addedRecord->getSpecialty() !== null) {
            $rolesToRemove[] = $soldier->getSpecialty()->role;
        }

        $rolesToKeep = $this->getRolesFromSecondaryAssignments($soldier);
        foreach ($rolesToRemove as $i => $toRemove) {
            if (isset($rolesToKeep[$toRemove->getId()])) {
                unset($rolesToRemove[$i]);
            }
        }

        $this->removeRoles($forumUser, $rolesToRemove);
        $this->addRoles($forumUser, $rolesToAdd);
    }

    public function preRemove(AssignmentRecord $deletedRecord): void
    {
        if (!$this->pluginVersionChecker->isVersionInstalled('forumify-milhq-plugin', 'premium')) {
            return;
        }

        $soldier = $deletedRecord->getSoldier();
        $forumUser = $soldier->getUser();
        if ($forumUser === null) {
            return;
        }

        $rolesToKeep = $this->getRolesFromSecondaryAssignments($soldier, $deletedRecord);
        foreach ($this->getRoles($soldier) as $role) {
            $rolesToKeep[$role->getId()] = $role;
        }

        $rolesToRemove = $this->getRoles($deletedRecord);
        foreach ($rolesToRemove as $i => $toRemove) {
            if (isset($rolesToKeep[$toRemove->getId()])) {
                unset($rolesToRemove[$i]);
            }
        }
        $this->removeRoles($forumUser, $rolesToRemove);
    }

    /**
     * @return array<Role>
     */
    private function getRoles(AssignmentRecord|Soldier $thing): array
    {
        return array_values(array_filter([
            $thing->getStatus()?->role,
            $thing->getUnit()?->role,
            $thing->getPosition()?->role,
            $thing->getSpecialty()?->role,
        ]));
    }

    /**
     * @param array<Role> $roles
     */
    private function addRoles(User $user, array $roles): void
    {
        $userRoles = $user->getRoleEntities();
        foreach ($roles as $role) {
            if (!$userRoles->contains($role)) {
                $userRoles->add($role);
            }
        }
    }

    /**
     * @param array<Role> $roles
     */
    private function removeRoles(User $user, array $roles): void
    {
        $userRoles = $user->getRoleEntities();
        foreach ($roles as $role) {
            if ($userRoles->contains($role)) {
                $userRoles->removeElement($role);
            }
        }
    }

    /**
     * @return array<Role>
     */
    private function getRolesFromSecondaryAssignments(Soldier $soldier, ?AssignmentRecord $ignore = null): array
    {
        $qb = $this
            ->assignmentRecordRepository
            ->createQueryBuilder('ar')
            ->where('ar.type = :type')
            ->andWhere('ar.soldier = :soldier')
            ->setParameter('type', AssignmentRecord::TYPE_SECONDARY)
            ->setParameter('soldier', $soldier)
        ;

        if ($ignore !== null) {
            $qb
                ->andWhere('ar != :record')
                ->setParameter('record', $ignore)
            ;
        }

        $roles = array_merge(...array_map($this->getRoles(...), $qb->getQuery()->getResult()));
        $roleMap = [];
        foreach ($roles as $role) {
            $roleMap[$role->getId()] = $role;
        }
        return $roleMap;
    }
}
