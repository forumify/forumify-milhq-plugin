<?php

declare(strict_types=1);

namespace Forumify\Milhq\Admin\Service;

use Doctrine\ORM\EntityManagerInterface;
use Forumify\Milhq\Entity\Award;
use Forumify\Milhq\Entity\AwardTier;
use Forumify\Milhq\Entity\Record\AwardRecord;
use Forumify\Milhq\Repository\AwardRecordRepository;

class AwardToTierService
{
    public function __construct(
        private readonly AwardRecordRepository $awardRecordRepository,
        private readonly EntityManagerInterface $em,
    ) {
    }

    /**
     * @param array{tierName?: string, targetAwardName?: string, targetTierName?: string} $options
     */
    public function awardToTier(Award $award, Award $targetAward, array $options = []): void
    {
        if ($targetAward->tiers->isEmpty()) {
            $this->upgradeToTiered($targetAward, $options['targetAwardName'] ?? null, $options['targetTierName'] ?? null);
            $this->em->flush();
        }

        $tier = $this->createTier($award, $targetAward, $options['tierName'] ?? null);
        $this->migrateRecords($award, $targetAward, $tier);

        $this->em->remove($award);
        $this->em->flush();
    }

    private function upgradeToTiered(Award $award, ?string $rename, ?string $tierName): void
    {
        $recordCount = $this->awardRecordRepository->count(['award' => $award]);
        if ($recordCount === 0) {
            return;
        }

        $tier = $this->createTier($award, $award, $tierName);
        if ($rename) {
            $award->setName($rename);
        }

        $this->migrateRecords($award, $award, $tier);
    }

    private function createTier(Award $source, Award $parent, ?string $rename): AwardTier
    {
        $tier = new AwardTier();
        $tier->name = $rename ?: $source->getName();
        $tier->image = $source->getImage();
        $parent->addTier($tier);

        $this->em->persist($tier);
        return $tier;
    }

    private function migrateRecords(Award $award, Award $newAward, AwardTier $tier): void
    {
        /** @var array<AwardRecord> $records */
        $records = $this->awardRecordRepository->findBy(['award' => $award]);
        foreach ($records as $record) {
            $record->setAward($newAward);
            $record->setTier($tier);
        }
    }
}
