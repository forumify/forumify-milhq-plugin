<?php

declare(strict_types=1);

namespace Forumify\Milhq\Discord\Command;

use Forumify\Discord\Api\DTO\DiscordCommandOption;
use Forumify\Discord\Api\DTO\DiscordCommandResult;
use Forumify\Discord\Api\DTO\DiscordEmbed;
use Forumify\Discord\Api\Resource\DiscordCommandRun;
use Forumify\Discord\Discord\DiscordCommandInterface;
use Forumify\Milhq\Discord\Service\HtmlToDiscordTextConverter;
use Forumify\Milhq\Entity\Award;
use Forumify\Milhq\Repository\AwardRepository;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\UrlHelper;

class AwardCommand implements DiscordCommandInterface
{
    public function __construct(
        private readonly AwardRepository $awardRepository,
        private readonly HtmlToDiscordTextConverter $htmlToDiscordTextConverter,
        private readonly Packages $packages,
        private readonly UrlHelper $urlHelper,
    ) {
    }

    public function getName(): string
    {
        return 'milhq-award';
    }

    public function getDescription(): string
    {
        return 'Shows MILHQ award information.';
    }

    public function getOptions(): array
    {
        return [
            new DiscordCommandOption()
                ->setName('name')
                ->setDescription('The (partial) name of the award to look up, i.e.: "Medal of Honor".')
                ->setRequired(),
        ];
    }

    public function run(DiscordCommandRun $command): DiscordCommandResult
    {
        $result = new DiscordCommandResult();

        $name = $command->options['name'] ?? '';
        if (trim($name) === '') {
            $result->content = 'Please provide an award name to search for.';
            return $result;
        }

        $awards = $this->awardRepository->findByNameLike($name);
        if (empty($awards)) {
            $result->content = "We could not find any awards matching \"$name\".";
            return $result;
        }

        foreach ($awards as $award) {
            $result->embeds[] = $this->createEmbed($award);
        }

        return $result;
    }

    private function createEmbed(Award $award): DiscordEmbed
    {
        $embed = new DiscordEmbed(
            title: $award->getName(),
            description: $this->htmlToDiscordTextConverter->convert($award->getDescription()),
        );

        $image = $award->getImage();
        if ($image !== null) {
            $imageUrl = $this->packages->getUrl($image, 'milhq.asset');
            $embed->setThumbnail($this->urlHelper->getAbsoluteUrl($imageUrl));
        }

        return $embed;
    }
}
