<?php

declare(strict_types=1);

namespace Forumify\Milhq\Automation\Trigger;

use Forumify\Automation\Repository\AutomationRepository;
use Forumify\Automation\Scheduler\AutomationScheduler;
use Forumify\Automation\Trigger\TriggerInterface;
use Forumify\Milhq\Admin\Service\RecordService;
use Forumify\Milhq\Event\RecordsCreatedEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(method: 'trigger')]
class RecordTrigger implements TriggerInterface
{
    public function __construct(
        private readonly AutomationRepository $automationRepository,
        private readonly AutomationScheduler $automationScheduler,
    ) {
    }

    public static function getType(): string
    {
        return 'MILHQ: New Record';
    }

    public function getPayloadFormType(): ?string
    {
        return RecordTriggerType::class;
    }

    public function trigger(RecordsCreatedEvent $event): void
    {

        $automations = $this->automationRepository->findByTriggerType(self::getType());
        foreach ($automations as $automation) {
            $recordType = $automation->getTriggerArguments()['recordType'] ?? null;
            foreach ($event->records as $record) {
                $type = RecordService::classToType($record);
                if ($recordType === null || $type === $recordType) {
                    $this->automationScheduler->schedule($automation, [
                        'record' => $record,
                        'type' => $type,
                    ]);
                }
            }
        }
    }
}
