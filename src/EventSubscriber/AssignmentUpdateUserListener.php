<?php

declare(strict_types=1);

namespace Forumify\Milhq\EventSubscriber;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Forumify\Milhq\Entity\Soldier;
use Forumify\Milhq\Entity\Record\AssignmentRecord;
use Forumify\Milhq\Repository\AssignmentRecordRepository;

#[AsEntityListener(Events::prePersist, 'prePersist', entity: AssignmentRecord::class)]
#[AsEntityListener(Events::preRemove, 'preRemove', entity: AssignmentRecord::class)]
class AssignmentUpdateUserListener
{
    public function __construct(private readonly AssignmentRecordRepository $assignmentRecordRepository)
    {
    }

    public function prePersist(AssignmentRecord $record): void
    {
        if ($record->getType() !== 'primary') {
            return;
        }

        $user = $record->getSoldier();
        if ($status = $record->getStatus()) {
            $user->setStatus($status);
        }

        if ($unit = $record->getUnit()) {
            $user->setUnit($unit);
        }

        if ($position = $record->getPosition()) {
            $user->setPosition($position);
        }

        if ($specialty = $record->getSpecialty()) {
            $user->setSpecialty($specialty);
        }
    }

    public function preRemove(AssignmentRecord $record): void
    {
        if ($record->getType() !== 'primary') {
            return;
        }

        $user = $record->getSoldier();
        $previousFinder = $this->getPrevious($user, $record);

        if ($record->getStatus() !== null) {
            $user->setStatus($previousFinder('status')?->getStatus());
        }

        if ($record->getUnit() !== null) {
            $user->setUnit($previousFinder('unit')?->getUnit());
        }

        if ($record->getPosition() !== null) {
            $user->setPosition($previousFinder('position')?->getPosition());
        }

        if ($record->getSpecialty() !== null) {
            $user->setSpecialty($previousFinder('specialty')?->getSpecialty());
        }
    }

    private function getPrevious(Soldier $user, AssignmentRecord $record): callable
    {
        return fn(string $thing): ?AssignmentRecord => $this->assignmentRecordRepository
            ->createQueryBuilder('ar')
            ->where('ar.type = :type')
            ->andWhere('ar != :record')
            ->andWhere('ar.user = :user')
            ->andWhere("ar.$thing IS NOT NULL")
            ->setParameter('record', $record)
            ->setParameter('type', 'primary')
            ->setParameter('user', $user)
            ->orderBy('ar.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
