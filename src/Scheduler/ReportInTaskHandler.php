<?php

declare(strict_types=1);

namespace Forumify\Milhq\Scheduler;

use Forumify\Milhq\Service\ReportInService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Scheduler\Attribute\AsCronTask;

#[AsCommand('milhq:report-in:run-checks')]
#[AsCronTask('0 8 * * *', jitter: 1800)]
class ReportInTaskHandler
{
    public function __construct(private readonly ReportInService $reportInService)
    {
    }

    public function __invoke(): int
    {
        if (!$this->reportInService->isEnabled()) {
            return Command::SUCCESS;
        }

        $this->reportInService->runReportInChecks();
        return Command::SUCCESS;
    }
}
