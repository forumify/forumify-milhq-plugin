<?php

declare(strict_types=1);

namespace Forumify\Milhq\Twig;

use Forumify\Milhq\Entity\Position;
use Forumify\Milhq\Entity\Unit;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class UnitExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('milhq_unit_is_supervisor', $this->isSupervisor(...)),
        ];
    }

    public function isSupervisor(Unit $unit, Position $position): bool
    {
        foreach ($unit->supervisors as $supervisor) {
            if ($supervisor->getId() === $position->getId()) {
                return true;
            }
        }
        return false;
    }
}
