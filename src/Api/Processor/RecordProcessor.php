<?php

declare(strict_types=1);

namespace Forumify\Milhq\Api\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Forumify\Milhq\Admin\Service\RecordService;
use Forumify\Milhq\Entity\Record\RecordInterface;

/**
 * @implements ProcessorInterface<RecordInterface, RecordInterface>
 */
class RecordProcessor implements ProcessorInterface
{
    public function __construct(private readonly RecordService $recordService)
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): RecordInterface
    {
        $this->recordService->createRecords($data, $data->shouldSendNotification());
        return $data;
    }
}
