<?php

declare(strict_types=1);

namespace Forumify\Milhq\Event\Listener;

use Forumify\Core\Entity\Notification;
use Forumify\Core\Notification\NotificationService;
use Forumify\Milhq\Admin\Service\RecordService;
use Forumify\Milhq\Entity\Record\AssignmentRecord;
use Forumify\Milhq\Entity\Record\AwardRecord;
use Forumify\Milhq\Entity\Record\QualificationRecord;
use Forumify\Milhq\Entity\Record\RankRecord;
use Forumify\Milhq\Entity\Record\RecordInterface;
use Forumify\Milhq\Event\RecordsCreatedEvent;
use Forumify\Milhq\Notification\NewRecordNotificationType;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
class SendRecordNotificationListener
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {
    }

    public function __invoke(RecordsCreatedEvent $event): void
    {
        if (!$event->sendNotification) {
            return;
        }

        foreach ($event->records as $record) {
            $this->sendNotification($record);
        }
    }

    private function sendNotification(RecordInterface $record): void
    {
        $user = $record->getSoldier()->getUser();
        if ($user === null) {
            return;
        }

        $type = RecordService::classToType($record);

        $data = ['text' => $record->getText()];
        if ($record instanceof AwardRecord) {
            $data['award']['name'] = $record->getAward()->getName();
        } elseif ($record instanceof RankRecord) {
            $data['rank']['name'] = $record->getRank()->getName();
            $data['type'] = $record->getType();
        } elseif ($record instanceof QualificationRecord) {
            $data['qualification']['name'] = $record->getQualification()->getName();
        } elseif ($record instanceof AssignmentRecord) {
            if ($record->getPosition() === null && $record->getUnit() === null) {
                return;
            }

            $soldier = $record->getSoldier();
            $data['position']['name'] = ($record->getPosition() ?? $soldier->getPosition())?->getName() ?? '';
            $data['unit']['name'] = ($record->getUnit() ?? $soldier->getUnit())?->getName() ?? '';
        }

        $this->notificationService->sendNotification(new Notification(
            NewRecordNotificationType::TYPE,
            $user,
            [
                'data' => $data,
                'type' => $type,
                'soldier' => $record->getSoldier()->getId(),
            ]
        ));
    }
}
