<?php

declare(strict_types=1);

namespace Forumify\Milhq\Discord\Command;

use Forumify\Core\Twig\Extension\CoreRuntime;
use Forumify\Discord\Api\DTO\DiscordCommandOption;
use Forumify\Discord\Api\Resource\DiscordCommandRun;
use Forumify\Discord\Api\DTO\DiscordCommandResult;
use Forumify\Discord\Api\DTO\DiscordEmbed;
use Forumify\Discord\Discord\DiscordCommandInterface;
use Forumify\Discord\Service\ImageGridBuilder;
use Forumify\Milhq\Entity\Equipment;
use Forumify\Milhq\Entity\Record\AssignmentRecord;
use Forumify\Milhq\Entity\Soldier;
use Forumify\Milhq\Repository\AssignmentRecordRepository;
use Forumify\Milhq\Repository\SoldierRepository;
use Forumify\Milhq\Service\SoldierService;
use Forumify\OAuth\Idp\DiscordIdp;
use Forumify\OAuth\Repository\IdentityProviderUserRepository;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\UrlHelper;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SoldierCommand implements DiscordCommandInterface
{
    public function __construct(
        private readonly SoldierRepository $soldierRepository,
        private readonly IdentityProviderUserRepository $idpUserRepository,
        private readonly SoldierService $soldierService,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly Packages $packages,
        private readonly UrlHelper $urlHelper,
        private readonly AssignmentRecordRepository $assignmentRecordRepository,
        private readonly CoreRuntime $coreRuntime,
        private readonly FilesystemOperator $milhqAssetStorage,
    ) {
    }

    public function getName(): string
    {
        return 'milhq-soldier';
    }

    public function getDescription(): string
    {
        return 'Shows a MILHQ soldier profile.';
    }

    public function getOptions(): array
    {
        return [
            new DiscordCommandOption()
                ->setName('name')
                ->setDescription('Optional soldier name, if left blank, it will show your own profile. i.e.: "John Doe".'),
        ];
    }

    public function run(DiscordCommandRun $command): DiscordCommandResult
    {
        $result = new DiscordCommandResult();

        $soldier = $this->getSoldierFromCmd($command);
        if ($soldier === null) {
            $result->content = "We could not find any soldier profiles matching your request. Try coupling your Discord account in your forum account settings, or log in to your forum account using Discord at least once.\n\nIf your forum does not support log in by Discord, provide the `name` option to the command.";
            return $result;
        }

        $result->embeds[] = $this->createEmbed($soldier);

        return $result;
    }

    private function getSoldierFromCmd(DiscordCommandRun $command): ?Soldier
    {
        $name = $command->options['name'] ?? null;
        if (!empty($name)) {
            $res = $this->soldierRepository->findBy(['name' => $name], limit: 1);
            return reset($res) ?: null;
        }

        $self = $this->idpUserRepository->findOneByExternalIdAndIdpType($command->discordUserId, DiscordIdp::getType());
        if ($self === null) {
            return null;
        }

        return $this->soldierService->getSoldier($self->getUser());
    }

    private function createEmbed(Soldier $soldier): DiscordEmbed
    {
        $embed = new DiscordEmbed(
            title: $soldier->getName(),
            url: $this->urlGenerator->generate('milhq_soldier', ['id' => $soldier->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
        );

        $status = $soldier->getStatus();
        if ($status) {
            $embed->addField('Status', $status->getName());
        }

        $rank = $soldier->getRank();
        if ($rank !== null) {
            $embed->title = $rank->getAbbreviation() . ' ' . $embed->title;
            $embed->addField('Rank', "{$rank->getPaygrade()} {$rank->getName()} ({$rank->getAbbreviation()})");

            $rankImg = $rank->getImage();
            if ($rankImg) {
                $rankUrl = $this->packages->getUrl($rankImg, 'milhq.asset');
                $embed->setThumbnail($this->urlHelper->getAbsoluteUrl($rankUrl));
            }
        }

        $tis = $this->soldierService->getTimeInService($soldier);
        $embed->addField('Time In Service', $tis, true);

        $tig = $this->soldierService->getTimeInGrade($soldier);
        $embed->addField('Time In Grade', $tig, true);

        $lastReportIn = $this->soldierService->getLastReportInDate($soldier);
        if ($lastReportIn !== null) {
            $embed->addField('Last Report In', $this->coreRuntime->formatDate($lastReportIn), true);
        } else {
            $embed->addField('', '', true);
        }

        $assignment = $this->formatAssignment($soldier);
        if (!empty($assignment)) {
            $embed->addField('Assignment', $assignment);
        }

        $secondaryAssignmentRecords = $this->assignmentRecordRepository->findBy([
            'type' => 'secondary',
            'soldier' => $soldier,
        ]);
        $secondaryAssignments = array_filter(array_map($this->formatAssignment(...), $secondaryAssignmentRecords));
        if (!empty($secondaryAssignments)) {
            sort($secondaryAssignments);
            $embed->addField('Secondary Assignments', implode("\n", $secondaryAssignments));
        }

        $supervisors = $this->soldierService->getSupervisors($soldier);
        $supervisors = implode(', ', array_map(fn (Soldier $s) => $s->getName(), $supervisors));
        if (!empty($supervisors)) {
            $embed->addField('Supervisors', $supervisors);
        }

        $equipment = $this->soldierService->getEquipment($soldier);
        $primaryWeapons = implode(', ', array_map(fn (Equipment $e) => $e->getName(), $equipment['primaryWeapons']));
        $primaryWeapons = empty($primaryWeapons) ? '' : ('Primary Weapon(s): ' . $primaryWeapons);

        $secondaryWeapons = implode(', ', array_map(fn (Equipment $e) => $e->getName(), $equipment['secondaryWeapons']));
        $secondaryWeapons = empty($secondaryWeapons) ? '' : ('Secondary Weapon(s): ' . $secondaryWeapons);

        $vehicles = implode(', ', array_map(fn (Equipment $e) => $e->getName(), $equipment['vehicles']));
        $vehicles = empty($vehicles) ? '' : ('Vehicle(s): ' . $vehicles);

        $equipment = implode("\n", array_filter([$primaryWeapons, $secondaryWeapons, $vehicles]));
        if (!empty($equipment)) {
            $embed->addField('Equipment', $equipment);
        }

        $uniform = $soldier->getUniform();
        $signature = $soldier->getSignature();
        if ($uniform || $signature) {
            $imgBuilder = (new ImageGridBuilder())
                ->setColumns(1)
                ->setCellWidth(800)
                ->setCellHeight(null)
            ;

            if ($uniform) {
                $imgBuilder->addImage($this->milhqAssetStorage, $uniform);
            }

            if ($signature) {
                $imgBuilder->addImage($this->milhqAssetStorage, $signature);
            }

            $filename = "discord/profile/{$soldier->getId()}.webp";
            $this->milhqAssetStorage->write($filename, (string)$imgBuilder->build());

            $profileImg = $this->packages->getUrl($filename, 'milhq.asset');
            $embed->setImage($this->urlHelper->getAbsoluteUrl($profileImg));
        }

        return $embed;
    }

    private function formatAssignment(Soldier|AssignmentRecord $subject): string
    {
        $parts = [];
        if ($speciality = $subject->getSpecialty()) {
            $parts[] = $speciality->getAbbreviation();
        }
        if ($unit = $subject->getUnit()) {
            $unitName = $unit->getName();
            if ($designation = $unit->getDesignation()) {
                $unitName .= " ($designation)";
            }
            $parts[] = $unitName;
        }
        if ($position = $subject->getPosition()) {
            $parts[] = $position->getName();
        }

        return implode(' - ', $parts);
    }
}
