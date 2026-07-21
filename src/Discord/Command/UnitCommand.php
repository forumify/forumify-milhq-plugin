<?php

declare(strict_types=1);

namespace Forumify\Milhq\Discord\Command;

use Forumify\Discord\Api\DTO\DiscordCommandOption;
use Forumify\Discord\Api\DTO\DiscordCommandResult;
use Forumify\Discord\Api\DTO\DiscordEmbed;
use Forumify\Discord\Api\Resource\DiscordCommandRun;
use Forumify\Discord\Discord\DiscordCommandInterface;
use Forumify\Milhq\Discord\Service\HtmlToDiscordTextConverter;
use Forumify\Milhq\Entity\Equipment;
use Forumify\Milhq\Entity\Soldier;
use Forumify\Milhq\Entity\Unit;
use Forumify\Milhq\Repository\UnitRepository;
use Forumify\Milhq\Service\SoldierService;

class UnitCommand implements DiscordCommandInterface
{
    public function __construct(
        private readonly UnitRepository $unitRepository,
        private readonly SoldierService $soldierService,
        private readonly HtmlToDiscordTextConverter $htmlToDiscordTextConverter,
    ) {
    }

    public function getName(): string
    {
        return 'milhq-unit';
    }

    public function getDescription(): string
    {
        return 'Shows MILHQ unit information.';
    }

    public function getOptions(): array
    {
        return [
            new DiscordCommandOption()
                ->setName('name')
                ->setDescription('The (partial) name of the unit to look up, i.e.: "First Squad".')
                ->setRequired(),
        ];
    }

    public function run(DiscordCommandRun $command): DiscordCommandResult
    {
        $result = new DiscordCommandResult();

        $name = $command->options['name'] ?? '';
        if (trim($name) === '') {
            $result->content = 'Please provide a unit name to search for.';
            return $result;
        }

        $units = $this->unitRepository->findByNameLike($name);
        $unit = reset($units);
        if (!$unit) {
            $result->content = "We could not find any units matching \"$name\".";
            return $result;
        }

        $result->embeds[] = $this->createEmbed($unit);

        return $result;
    }

    private function createEmbed(Unit $unit): DiscordEmbed
    {
        $embed = new DiscordEmbed(
            title: $unit->getName(),
            description: $this->htmlToDiscordTextConverter->convert($unit->getDescription()),
        );

        $designation = $unit->getDesignation();
        if (!empty($designation)) {
            $embed->addField('Designation', $designation, true);
        }

        $vehicles = implode(', ', array_map(fn (Equipment $e) => $e->getName(), $unit->getVehicles()->toArray()));
        if (!empty($vehicles)) {
            $embed->addField('Vehicles', $vehicles, true);
        }

        $supervisors = $this->soldierService->getUnitSupervisors($unit);
        $supervisors = implode(', ', array_map($this->formatSoldier(...), $supervisors));
        if (!empty($supervisors)) {
            $embed->addField('Supervisors', $supervisors);
        }

        $soldiers = $this->soldierService->getSoldiersInUnit($unit);
        if (!empty($soldiers)) {
            $members = implode("\n", array_map(
                fn (Soldier $soldier) => '**' . $this->formatSoldier($soldier) . '** ' . ($soldier->getPosition()?->getName() ?? ''),
                $soldiers,
            ));
            $embed->addField('Soldiers', $members);
        }

        return $embed;
    }

    private function formatSoldier(Soldier $soldier): string
    {
        $rank = $soldier->getRank();
        $rankAbbreviation = $rank !== null ? $rank->getAbbreviation() . ' ' : '';

        return trim($rankAbbreviation . $soldier->getName());
    }
}
