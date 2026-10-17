<?php

declare(strict_types=1);

namespace Forumify\Milhq\Api\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Forumify\Milhq\Entity\FormField;
use Forumify\Milhq\Entity\FormSubmission;
use Forumify\Milhq\Repository\FormSubmissionRepository;
use Forumify\Milhq\Service\SoldierService;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * @implements ProcessorInterface<FormSubmission, FormSubmission>
 */
class FormSubmissionCreateProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly SoldierService $soldierService,
        private readonly FormSubmissionRepository $formSubmissionRepository,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): FormSubmission
    {
        $soldier = $this->soldierService->getLoggedInSoldier();
        if ($soldier === null) {
            throw new AccessDeniedHttpException('You need to enlist before you can create form submissions.');
        }

        $form = $data->getForm();
        $data->setSoldier($soldier);
        $data->setStatus($form->getDefaultStatus());
        $data->setData($this->validateData($form->getFields()->toArray(), $data->getData() ?? []));

        $this->formSubmissionRepository->save($data);
        return $data;
    }

    /**
     * @param array<FormField> $fields
     * @param array<string, mixed> $submitted
     * @return array<string, mixed>
     */
    private function validateData(array $fields, array $submitted): array
    {
        $data = [];
        $errors = [];
        foreach ($fields as $field) {
            $key = $field->getKey();
            $value = $submitted[$key] ?? null;

            if ($value === null || $value === '') {
                if ($field->isRequired()) {
                    $errors[] = "\"$key\" is required.";
                }
                continue;
            }

            if ($field->getType() === 'select' && !in_array($value, $this->getChoices($field), true)) {
                $errors[] = "\"$key\" must be one of: " . implode(', ', $this->getChoices($field)) . '.';
                continue;
            }

            $data[$key] = $value;
        }

        if (!empty($errors)) {
            throw new UnprocessableEntityHttpException(implode(' ', $errors));
        }

        return $data;
    }

    /**
     * @return list<mixed>
     */
    private function getChoices(FormField $field): array
    {
        return array_column($field->fieldOptions['options'] ?? [], 'key');
    }
}
