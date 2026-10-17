<?php

declare(strict_types=1);

namespace Forumify\Milhq\Api\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Forumify\Milhq\Entity\MissionRsvp;
use Forumify\Milhq\Repository\MissionRSVPRepository;
use Forumify\Milhq\Service\SoldierService;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * @implements ProcessorInterface<MissionRsvp, MissionRsvp>
 */
class MissionRsvpProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly SoldierService $soldierService,
        private readonly MissionRSVPRepository $missionRSVPRepository,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): MissionRsvp
    {
        $soldier = $this->soldierService->getLoggedInSoldier();
        if ($soldier === null) {
            throw new AccessDeniedHttpException('You need to be a soldier to RSVP to missions.');
        }

        $mission = $data->getMission();
        if (!$mission->canRsvp()) {
            throw new UnprocessableEntityHttpException('This mission no longer accepts RSVPs.');
        }

        $rsvp = $this->missionRSVPRepository->findOneBy(['mission' => $mission, 'soldier' => $soldier]) ?? $data;
        $rsvp->setSoldier($soldier);
        $rsvp->setGoing($data->isGoing());
        $this->missionRSVPRepository->save($rsvp);

        return $rsvp;
    }
}
