<?php

declare(strict_types=1);

namespace Forumify\Milhq\Admin\Service;

use Doctrine\ORM\EntityManagerInterface;
use Forumify\Milhq\Entity\Qualification;
use Forumify\Milhq\Entity\QualificationTier;
use Forumify\Milhq\Entity\Record\QualificationRecord;
use Forumify\Milhq\Repository\QualificationRecordRepository;

class QualificationToTierService
{
    public function __construct(
        private readonly QualificationRecordRepository $qualificationRecordRepository,
        private readonly EntityManagerInterface $em,
    ) {
    }

    /**
     * @param array{tierName?: string, targetQualificationName?: string, targetTierName?: string} $options
     */
    public function qualificationToTier(Qualification $qualification, Qualification $targetQualification, array $options = []): void
    {
        if ($targetQualification->tiers->isEmpty()) {
            $this->upgradeToTiered($targetQualification, $options['targetQualificationName'] ?? null, $options['targetTierName'] ?? null);
            $this->em->flush();
        }

        $tier = $this->qualToTier($qualification, $targetQualification, $options['tierName'] ?? null);
        $this->migrateRecords($qualification, $targetQualification, $tier);

        $this->em->remove($qualification);
        $this->em->flush();
    }

    private function upgradeToTiered(Qualification $qualification, ?string $rename, ?string $tierName): void
    {
        $recordCount = $this->qualificationRecordRepository->count(['qualification' => $qualification]);
        if ($recordCount === 0) {
            return;
        }

        $tier = $this->qualToTier($qualification, $qualification, $tierName);
        if ($rename) {
            $qualification->setName($rename);
        }

        $this->migrateRecords($qualification, $qualification, $tier);
    }

    private function qualToTier(Qualification $source, Qualification $parent, ?string $rename): QualificationTier
    {
        $tier = new QualificationTier();
        $tier->name = $rename ?: $source->getName();
        $tier->image = $source->getImage();
        $parent->addTier($tier);

        $this->em->persist($tier);
        return $tier;
    }

    private function migrateRecords(Qualification $qualification, Qualification $newQualification, QualificationTier $tier): void
    {
        /** @var array<QualificationRecord> $records */
        $records = $this->qualificationRecordRepository->findBy(['qualification' => $qualification]);
        foreach ($records as $record) {
            $record->setQualification($newQualification);
            $record->setTier($tier);
        }
    }
}
