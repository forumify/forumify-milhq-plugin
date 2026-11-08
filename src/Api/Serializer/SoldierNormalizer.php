<?php

declare(strict_types=1);

namespace Forumify\Milhq\Api\Serializer;

use DateInterval;
use Forumify\Milhq\Entity\Soldier;
use Forumify\Milhq\Service\SoldierService;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class SoldierNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    private const string ALREADY_CALLED = 'milhq_soldier_normalizer_called';

    public function __construct(private readonly SoldierService $soldierService)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function normalize(mixed $data, ?string $format = null, array $context = []): array
    {
        $context[self::ALREADY_CALLED] = true;

        /** @var array<string, mixed> $normalized */
        $normalized = $this->normalizer->normalize($data, $format, $context);

        $status = $data->getStatus();
        $normalized['timeInService'] = $this->toArray(
            $this->soldierService->getTimeInService($data),
            $status->freezeTimeInService ?? false,
        );

        $timeInGrade = $this->soldierService->getTimeInGrade($data);
        if ($timeInGrade !== null) {
            $normalized['timeInGrade'] = $this->toArray($timeInGrade, $status->freezeTimeInGrade ?? false);
        }

        return $normalized;
    }

    /**
     * @return array{days: int, frozen: bool}
     */
    private function toArray(DateInterval $interval, bool $frozen): array
    {
        return ['days' => (int)$interval->days, 'frozen' => $frozen];
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof Soldier
            && !isset($context[self::ALREADY_CALLED])
            && in_array('Soldier::read', $context['groups'] ?? [], true);
    }

    public function getSupportedTypes(?string $format): array
    {
        return [Soldier::class => false];
    }
}
