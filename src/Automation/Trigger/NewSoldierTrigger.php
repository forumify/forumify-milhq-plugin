<?php

declare(strict_types=1);

namespace Forumify\Milhq\Automation\Trigger;

use Forumify\Automation\Repository\AutomationRepository;
use Forumify\Automation\Scheduler\AutomationScheduler;
use Forumify\Automation\Trigger\TriggerInterface;
use Forumify\Milhq\Event\SoldierEnlistedEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(method: 'trigger')]
class NewSoldierTrigger implements TriggerInterface
{
    public function __construct(
        private readonly AutomationRepository $automationRepository,
        private readonly AutomationScheduler $automationScheduler,
    ) {
    }

    public static function getType(): string
    {
        return 'MILHQ: New Soldier';
    }

    public function getPayloadFormType(): ?string
    {
        return null;
    }

    public function trigger(SoldierEnlistedEvent $event): void
    {
        $automations = $this->automationRepository->findByTriggerType(self::getType());
        foreach ($automations as $automation) {
            $this->automationScheduler->schedule($automation, [
                'submission' => $event->submission,
                'soldier' => $event->soldier,
            ]);
        }
    }
}
