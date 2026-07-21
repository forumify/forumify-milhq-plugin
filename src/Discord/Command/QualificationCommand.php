<?php

declare(strict_types=1);

namespace Forumify\Milhq\Discord\Command;

use Forumify\Discord\Api\DTO\DiscordCommandOption;
use Forumify\Discord\Api\DTO\DiscordCommandResult;
use Forumify\Discord\Api\DTO\DiscordEmbed;
use Forumify\Discord\Api\Resource\DiscordCommandRun;
use Forumify\Discord\Discord\DiscordCommandInterface;
use Forumify\Milhq\Discord\Service\HtmlToDiscordTextConverter;
use Forumify\Milhq\Entity\Qualification;
use Forumify\Milhq\Repository\QualificationRepository;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\UrlHelper;

class QualificationCommand implements DiscordCommandInterface
{
    public function __construct(
        private readonly QualificationRepository $qualificationRepository,
        private readonly HtmlToDiscordTextConverter $htmlToDiscordTextConverter,
        private readonly Packages $packages,
        private readonly UrlHelper $urlHelper,
    ) {
    }

    public function getName(): string
    {
        return 'milhq-qualification';
    }

    public function getDescription(): string
    {
        return 'Shows MILHQ qualification information.';
    }

    public function getOptions(): array
    {
        return [
            new DiscordCommandOption()
                ->setName('name')
                ->setDescription('The (partial) name of the qualification to look up, i.e.: "Combat Lifesaver".')
                ->setRequired(),
        ];
    }

    public function run(DiscordCommandRun $command): DiscordCommandResult
    {
        $result = new DiscordCommandResult();

        $name = $command->options['name'] ?? '';
        if (trim($name) === '') {
            $result->content = 'Please provide a qualification name to search for.';
            return $result;
        }

        $qualifications = $this->qualificationRepository->findByNameLike($name);
        if (empty($qualifications)) {
            $result->content = "We could not find any qualifications matching \"$name\".";
            return $result;
        }

        foreach ($qualifications as $qualification) {
            $result->embeds[] = $this->createEmbed($qualification);
        }

        return $result;
    }

    private function createEmbed(Qualification $qualification): DiscordEmbed
    {
        $embed = new DiscordEmbed(
            title: $qualification->getName(),
            description: $this->htmlToDiscordTextConverter->convert($qualification->getDescription()),
        );

        $image = $qualification->getImage();
        if ($image !== null) {
            $imageUrl = $this->packages->getUrl($image, 'milhq.asset');
            $embed->setThumbnail($this->urlHelper->getAbsoluteUrl($imageUrl));
        }

        return $embed;
    }
}
