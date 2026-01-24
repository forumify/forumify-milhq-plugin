<?php

declare(strict_types=1);

namespace Forumify\Milhq\Admin\Service;

use DateTime;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;
use Forumify\Milhq\Entity\Record\AssignmentRecord;
use Forumify\Milhq\Entity\Record\AwardRecord;
use Forumify\Milhq\Entity\Record\CombatRecord;
use Forumify\Milhq\Entity\Record\QualificationRecord;
use Forumify\Milhq\Entity\Record\RankRecord;
use Forumify\Milhq\Entity\Record\RecordInterface;
use Forumify\Milhq\Entity\Record\ServiceRecord;
use Forumify\Milhq\Event\RecordsCreatedEvent;
use Forumify\Milhq\Exception\MilhqException;
use Forumify\Milhq\Service\SoldierService;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class RecordService
{
    public function __construct(
        private readonly SoldierService $soldierService,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @throws MilhqException
     */
    public function createRecord(string $type, array $data): void
    {
        $sendNotification = $data['sendNotification'] ?? false;
        $soldiers = $data['soldiers'] ?? [];
        if (empty($soldiers)) {
            return;
        }

        $class = self::typeToClass($type);

        $records = [];
        foreach ($soldiers as $soldier) {
            /** @var RecordInterface $record */
            $record = new $class();
            $record->setSoldier($soldier);
            $record->setText($data['text'] ?? '');
            $record->setCreatedAt($data['created_at'] ?? new DateTime());
            $record->setDocument($data['document'] ?? null);

            if ($record instanceof AwardRecord) {
                $record->setAward($data['award']);
            } elseif ($record instanceof AssignmentRecord) {
                $record->setType($data['type'] ?? 'primary');
                $record->setStatus($data['status'] ?? null);
                $record->setSpecialty($data['specialty'] ?? null);
                $record->setUnit($data['unit'] ?? null);
                $record->setPosition($data['position'] ?? null);
            } elseif ($record instanceof RankRecord) {
                $record->setType($data['type']);
                $record->setRank($data['rank']);
            } elseif ($record instanceof QualificationRecord) {
                $record->setQualification($data['qualification']);
            }

            $records[] = $record;
        }
        $this->createRecords($records, $sendNotification);
    }

    /**
     * @param array<RecordInterface>|RecordInterface $records
     */
    public function createRecords(array|RecordInterface $records, bool $sendNotification): void
    {
        if (!is_array($records)) {
            $records = [$records];
        }

        if (empty($records)) {
            return;
        }

        $author = $this->soldierService->getLoggedInSoldier();
        foreach ($records as $record) {
            if ($record->getAuthor() === null) {
                $record->setAuthor($author);
            }
            $this->entityManager->persist($record);
        }
        $this->entityManager->flush();

        $this->eventDispatcher->dispatch(new RecordsCreatedEvent($records, $sendNotification));
    }

    /**
     * @return class-string<RecordInterface>
     */
    public static function typeToClass(string $type): string
    {
        return match ($type) {
            'service' => ServiceRecord::class,
            'award' => AwardRecord::class,
            'assignment' => AssignmentRecord::class,
            'combat' => CombatRecord::class,
            'rank' => RankRecord::class,
            'qualification' => QualificationRecord::class,
            default => ServiceRecord::class,
        };
    }

    public static function classToType(RecordInterface $record): string
    {
        $class = ClassUtils::getRealClass(get_class($record));

        return match ($class) {
            ServiceRecord::class => 'service',
            AwardRecord::class => 'award',
            AssignmentRecord::class => 'assignment',
            CombatRecord::class => 'combat',
            RankRecord::class => 'rank',
            QualificationRecord::class => 'qualification',
            default => 'service',
        };
    }
}
