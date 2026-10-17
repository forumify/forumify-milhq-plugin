<?php

declare(strict_types=1);

namespace Forumify\Milhq\Api\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use DateTime;
use DateTimeInterface;
use Forumify\Milhq\Entity\FormField;
use Forumify\Milhq\Entity\FormSubmission;
use Forumify\Milhq\Form\SubmissionFormType;
use Forumify\Milhq\Repository\FormSubmissionRepository;
use Forumify\Milhq\Service\SoldierService;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @implements ProcessorInterface<FormSubmission, FormSubmission>
 */
class FormSubmissionCreateProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly SoldierService $soldierService,
        private readonly FormSubmissionRepository $formSubmissionRepository,
        private readonly ValidatorInterface $validator,
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

            $constraints = $field->isRequired() ? [new Assert\NotBlank()] : [];
            if ($value !== null && $value !== '') {
                $constraints[] = $this->getTypeConstraint($field);
            }

            $violations = $this->validator->validate($value, new Assert\Sequentially($constraints));
            foreach ($violations as $violation) {
                $errors[] = "\"$key\": {$violation->getMessage()}";
            }

            if ($value !== null && $value !== '' && $violations->count() === 0) {
                $data[$key] = $this->normalizeValue($field, $value);
            }
        }

        if (!empty($errors)) {
            throw new UnprocessableEntityHttpException(implode(' ', $errors));
        }

        return $data;
    }

    private function getTypeConstraint(FormField $field): Constraint
    {
        return match ($field->getType()) {
            'checkbox' => new Assert\Type('bool'),
            'color' => new Assert\Sequentially([new Assert\Type('string'), new Assert\CssColor(Assert\CssColor::HEX_LONG)]),
            'country' => new Assert\Sequentially([new Assert\Type('string'), new Assert\Country()]),
            'date' => new Assert\Sequentially([new Assert\Type('string'), new Assert\Date()]),
            'datetime' => new Assert\Sequentially([new Assert\Type('string'), new Assert\DateTime(DateTimeInterface::ATOM)]),
            'email' => new Assert\Sequentially([new Assert\Type('string'), new Assert\Email()]),
            'file' => new Assert\IsNull(message: 'File uploads are not supported through the API.'),
            'number' => new Assert\Type('numeric'),
            'select' => new Assert\Choice(array_column($field->fieldOptions['options'] ?? [], 'key')),
            'timezone' => new Assert\Sequentially([new Assert\Type('string'), new Assert\Timezone()]),
            default => new Assert\Type('string'),
        };
    }

    private function normalizeValue(FormField $field, mixed $value): mixed
    {
        return match ($field->getType()) {
            'date', 'datetime' => new DateTime($value)->format(SubmissionFormType::DATE_FORMAT),
            'number' => (float)$value,
            default => $value,
        };
    }
}
