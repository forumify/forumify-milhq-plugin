<?php

declare(strict_types=1);

namespace Forumify\Milhq\Api\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Forumify\Milhq\Api\Resource\RosterUnit;
use Forumify\Milhq\Repository\RosterRepository;
use Forumify\Milhq\Service\SoldierService;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @implements ProviderInterface<RosterUnit>
 */
class RosterUnitProvider implements ProviderInterface
{
    public function __construct(
        private readonly RosterRepository $rosterRepository,
        private readonly SoldierService $soldierService,
    ) {
    }

    /**
     * @return list<RosterUnit>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $roster = $this->rosterRepository->find($uriVariables['rosterId']);
        if ($roster === null) {
            throw new NotFoundHttpException("Roster with id {$uriVariables['rosterId']} does not exist.");
        }

        $units = [];
        foreach ($roster->getUnits() as $unit) {
            $units[] = new RosterUnit($unit, array_values($this->soldierService->getSoldiersInUnit($unit)));
        }

        return $units;
    }
}
