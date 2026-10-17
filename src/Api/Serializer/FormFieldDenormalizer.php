<?php

declare(strict_types=1);

namespace Forumify\Milhq\Api\Serializer;

use Forumify\Milhq\Entity\FormField;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class FormFieldDenormalizer implements DenormalizerInterface, DenormalizerAwareInterface
{
    use DenormalizerAwareTrait;

    private const string ALREADY_CALLED = 'milhq_form_field_denormalizer_called';

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): FormField
    {
        $context[self::ALREADY_CALLED] = true;

        /** @var FormField $field */
        $field = $this->denormalizer->denormalize($data, $type, $format, $context);
        $field->generateKeyIfEmpty();

        return $field;
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return $type === FormField::class && !isset($context[self::ALREADY_CALLED]);
    }

    public function getSupportedTypes(?string $format): array
    {
        return [FormField::class => false];
    }
}
