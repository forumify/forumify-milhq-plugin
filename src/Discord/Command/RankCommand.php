<?php

declare(strict_types=1);

namespace Forumify\Milhq\Discord\Command;

use Forumify\Discord\Api\DTO\DiscordCommandOption;
use Forumify\Discord\Api\DTO\DiscordCommandResult;
use Forumify\Discord\Api\DTO\DiscordEmbed;
use Forumify\Discord\Api\Resource\DiscordCommandRun;
use Forumify\Discord\Discord\DiscordCommandInterface;
use Forumify\Milhq\Discord\Service\HtmlToDiscordTextConverter;
use Forumify\Milhq\Entity\Rank;
use Forumify\Milhq\Repository\RankRepository;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\UrlHelper;

class RankCommand implements DiscordCommandInterface
{
    public function __construct(
        private readonly RankRepository $rankRepository,
        private readonly HtmlToDiscordTextConverter $htmlToDiscordTextConverter,
        private readonly Packages $packages,
        private readonly UrlHelper $urlHelper,
    ) {
    }

    public function getName(): string
    {
        return 'milhq-rank';
    }

    public function getDescription(): string
    {
        return 'Shows MILHQ rank information.';
    }

    public function getOptions(): array
    {
        return [
            new DiscordCommandOption()
                ->setName('name')
                ->setDescription('The (partial) name of the rank to look up, i.e.: "Sergeant".')
                ->setRequired(),
        ];
    }

    public function run(DiscordCommandRun $command): DiscordCommandResult
    {
        $result = new DiscordCommandResult();

        $name = $command->options['name'] ?? '';
        if (trim($name) === '') {
            $result->content = 'Please provide a rank name to search for.';
            return $result;
        }

        $ranks = $this->rankRepository->findByNameLike($name);
        if (empty($ranks)) {
            $result->content = "We could not find any ranks matching \"$name\".";
            return $result;
        }

        foreach ($ranks as $rank) {
            $result->embeds[] = $this->createEmbed($rank);
        }

        return $result;
    }

    private function createEmbed(Rank $rank): DiscordEmbed
    {
        $embed = new DiscordEmbed(
            title: $rank->getName(),
            description: $this->htmlToDiscordTextConverter->convert($rank->getDescription()),
        );

        $abbreviation = $rank->getAbbreviation();
        if (!empty($abbreviation)) {
            $embed->addField('Abbreviation', $abbreviation, true);
        }

        $paygrade = $rank->getPaygrade();
        if (!empty($paygrade)) {
            $embed->addField('Paygrade', $paygrade, true);
        }

        $image = $rank->getImage();
        if ($image !== null) {
            $imageUrl = $this->packages->getUrl($image, 'milhq.asset');
            $embed->setThumbnail($this->urlHelper->getAbsoluteUrl($imageUrl));
        }

        return $embed;
    }
}
