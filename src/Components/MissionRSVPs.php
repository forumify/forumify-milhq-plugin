<?php

declare(strict_types=1);

namespace Forumify\Milhq\Components;

use Forumify\Milhq\Entity\Mission;
use Forumify\Plugin\Attribute\PluginVersion;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[PluginVersion('forumify/forumify-milhq-plugin', 'premium')]
#[AsTwigComponent('Milhq\\MissionStats', '@ForumifyMilhqPlugin/frontend/components/mission_stats.html.twig')]
class MissionRSVPs
{
    public Mission $mission;

    public function getGoing(): int
    {
        $count = 0;

        foreach ($this->mission->getRsvps() as $rsvp) {
            if ($rsvp->isGoing() === true) {
                $count++;
            }
        }

        return $count;
    }

    public function getAbsent(): int
    {
        $count = 0;

        foreach ($this->mission->getRsvps() as $rsvp) {
            if ($rsvp->isGoing() === false) {
                $count++;
            }
        }

        return $count;
    }
}
