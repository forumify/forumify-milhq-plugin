<?php

declare(strict_types=1);

namespace Forumify\Milhq\Components;

use Forumify\Milhq\Entity\Mission;
use Forumify\Milhq\Entity\MissionRsvp;
use Forumify\Milhq\Entity\Soldier;
use Forumify\Milhq\Entity\Unit;
use Forumify\Milhq\Service\SoldierService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('Milhq\\MissionRoster', '@ForumifyMilhqPlugin/frontend/components/mission_roster.html.twig')]
class MissionRoster extends AbstractController
{

    public Mission $mission;

    private array $rsvpsByUserId = [];
    private ?array $unitsWithRSVPs = null;

    public function __construct(
        private readonly SoldierService $userService,
    ) {
    }

    public function getUnitsWithRSVPs(): array
    {
        if ($this->unitsWithRSVPs !== null) {
            return $this->unitsWithRSVPs;
        }

        $this->getRSVPsByUserId();
        $units = [];

        foreach ($this->rsvpsByUserId as $rsvp) {
            $user = $rsvp->getSoldier();
            $unit = $user?->getUnit();

            if ($unit && !isset($units[$unit->getId()])) {
                $units[$unit->getId()] = $unit;
            }
        }

        uasort($units, fn (Unit $a, Unit $b) => $a->getPosition() <=> $b->getPosition());
        $this->unitsWithRSVPs = $units;
        return $this->unitsWithRSVPs;
    }

    public function getUsersInUnitWithRSVPs(Unit $unit): array
    {
        $this->getRSVPsByUserId();
        $users = [];

        foreach ($unit->getSoldiers() as $user) {
            if (isset($this->rsvpsByUserId[$user->getId()])) {
                $users[$user->getId()] = $user;
            }
        }

        $this->userService->sortSoldiers($users);
        return $users;
    }

    public function getRSVPForUser(Soldier $user): ?MissionRsvp
    {
        $rsvps = $this->getRSVPsByUserId();
        return $rsvps[$user->getId()] ?? null;
    }

    /**
     * @return array<MissionRsvp>
     */
    private function getRSVPsByUserId(): array
    {
        if (!empty($this->rsvpsByUserId)) {
            return $this->rsvpsByUserId;
        }

        foreach ($this->mission->getRSVPs() as $rsvp) {
            if ($user = $rsvp->getSoldier()) {
                $this->rsvpsByUserId[$user->getId()] = $rsvp;
            }
        }

        return $this->rsvpsByUserId;
    }
}
