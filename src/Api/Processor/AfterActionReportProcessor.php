<?php

declare(strict_types=1);

namespace Forumify\Milhq\Api\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use Forumify\Milhq\Entity\AfterActionReport;
use Forumify\Milhq\Service\AfterActionReportService;

/**
 * @implements ProcessorInterface<AfterActionReport, AfterActionReport>
 */
class AfterActionReportProcessor implements ProcessorInterface
{
    public function __construct(private readonly AfterActionReportService $afterActionReportService)
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): AfterActionReport
    {
        $this->afterActionReportService->save($data, $operation instanceof Post);
        return $data;
    }
}
