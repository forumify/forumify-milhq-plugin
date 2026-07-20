<?php

declare(strict_types=1);

namespace Forumify\Milhq\Components;

use Forumify\Milhq\Entity\Roster;
use Forumify\Milhq\Entity\Soldier;
use Forumify\Milhq\Entity\Unit;
use Forumify\Milhq\Repository\RosterRepository;
use Forumify\Milhq\Service\SoldierService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent('Milhq\\Roster', '@ForumifyMilhqPlugin/frontend/components/roster.html.twig')]
class RosterView extends AbstractController
{
    use DefaultActionTrait;

    /** @var array<Roster> */
    public array $rosters;
    #[LiveProp(writable: true)]
    public ?int $selectedRosterId = null;

    public function __construct(
        private readonly RosterRepository $rosterRepository,
        private readonly SoldierService $soldierService,
    ) {
    }

    public function getRosters(): array
    {
        $this->rosters = $this->rosterRepository->findBy([], ['position' => 'ASC']);
        if ($this->selectedRosterId === null) {
            $first = reset($this->rosters);
            if ($first) {
                $this->selectedRosterId = $first->getId();
            }
        }
        return $this->rosters;
    }

    #[LiveAction]
    public function selectRoster(#[LiveArg] int $rosterId): void
    {
        $this->selectedRosterId = $rosterId;
    }

    public function getRoster(): ?Roster
    {
        foreach ($this->rosters as $r) {
            if ($r->getId() === $this->selectedRosterId) {
                return $r;
            }
        }
        return null;
    }

    /**
     * @return array<Soldier>
     */
    public function getSoldiersInUnit(Unit $unit): array
    {
        return $this->soldierService->getSoldiersInUnit($unit);
    }
}
