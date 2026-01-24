<?php

declare(strict_types=1);

namespace Forumify\Milhq\Components;

use Forumify\Milhq\Entity\Mission;
use Forumify\Milhq\Entity\MissionRsvp;
use Forumify\Milhq\Repository\MissionRSVPRepository;
use Forumify\Milhq\Service\SoldierService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent('Milhq\\MissionRSVPButton', '@ForumifyMilhqPlugin/frontend/components/mission_rsvp_button.html.twig')]
class MissionRSVPButton extends AbstractController
{
    use DefaultActionTrait;

    #[LiveProp]
    public Mission $mission;

    public function __construct(
        private readonly SoldierService $soldierService,
        private readonly MissionRSVPRepository $missionRSVPRepository,
    ) {
    }

    public function getRSVP(): ?MissionRsvp
    {
        $soldier = $this->soldierService->getLoggedInSoldier();
        if ($soldier === null) {
            return null;
        }

        return $this->missionRSVPRepository->findOneBy([
            'mission' => $this->mission,
            'user' => $soldier,
        ]);
    }

    #[LiveAction]
    public function toggle(#[LiveArg] bool $going): ?Response
    {
        $rsvp = $this->getRSVP() ?? $this->createMissionRSVP();
        if ($rsvp === null) {
            return null;
        }

        $rsvp->setGoing($going);
        $this->missionRSVPRepository->save($rsvp);

        return $this->redirectToRoute('milhq_missions_view', ['id' => $this->mission->getId()]);
    }

    #[LiveAction]
    public function cancel(): Response
    {
        $rsvp = $this->getRSVP();
        if ($rsvp !== null) {
            $this->missionRSVPRepository->remove($rsvp);
        }

        return $this->redirectToRoute('milhq_missions_view', ['id' => $this->mission->getId()]);
    }

    private function createMissionRSVP(): ?MissionRsvp
    {
        $soldier = $this->soldierService->getLoggedInSoldier();
        if ($soldier === null) {
            return null;
        }

        $rsvp = new MissionRsvp();
        $rsvp->setMission($this->mission);
        $rsvp->setSoldier($soldier);

        return $rsvp;
    }
}
