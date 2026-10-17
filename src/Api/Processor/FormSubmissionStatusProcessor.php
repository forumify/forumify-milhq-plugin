<?php

declare(strict_types=1);

namespace Forumify\Milhq\Api\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Forumify\Milhq\Admin\Service\SubmissionStatusUpdateService;
use Forumify\Milhq\Entity\FormSubmission;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * @implements ProcessorInterface<FormSubmission, FormSubmission>
 */
class FormSubmissionStatusProcessor implements ProcessorInterface
{
    public function __construct(private readonly SubmissionStatusUpdateService $submissionStatusUpdateService)
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): FormSubmission
    {
        $status = $data->getStatus();
        if ($status !== null && $status->getForm()->getId() !== $data->getForm()->getId()) {
            throw new UnprocessableEntityHttpException('This status does not belong to the form of this submission.');
        }

        $this->submissionStatusUpdateService->createStatusRecord($data, [
            'status' => $status,
            'reason' => $data->getStatusReason(),
            'sendNotification' => $data->sendNotification,
        ]);

        return $data;
    }
}
