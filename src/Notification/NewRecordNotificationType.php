<?php

declare(strict_types=1);

namespace Forumify\Milhq\Notification;

use Forumify\Core\Entity\Notification;
use Forumify\Core\Notification\AbstractEmailNotificationType;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class NewRecordNotificationType extends AbstractEmailNotificationType
{
    public const TYPE = 'milhq_new_record';

    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly Packages $packages,
    ) {
    }

    public function getType(): string
    {
        return self::TYPE;
    }

    public function getTitle(Notification $notification): string
    {
        $type = $this->getTypeFromContext($notification);
        return $this->translator->trans("milhq.notification.{$type}_record.title");
    }

    public function getDescription(Notification $notification): string
    {
        $type = $this->getTypeFromContext($notification);
        return $this->translator->trans(
            "milhq.notification.{$type}_record.description",
            $this->getDescriptionPayload($notification),
        );
    }

    private function getDescriptionPayload(Notification $notification): array
    {
        $context = $notification->getDeserializedContext();
        $type = $context['type'];
        $data = $context['data'];

        return match ($type) {
            'award' => [
                'award' => $data['award']['name'],
            ],
            'rank' => [
                'rank' => $data['rank']['name'],
                'type' => $data['type'],
            ],
            'assignment' => [
                'position' => $data['position']['name'],
                'unit' => $data['unit']['name'],
            ],
            'qualification' => [
                'qualification' => $data['qualification']['name'],
            ],
            default => ['text' => $data['text']],
        };
    }

    public function getImage(Notification $notification): string
    {
        return $this->packages->getUrl('bundles/forumifymilhqplugin/images/milhq.png');
    }

    public function getUrl(Notification $notification): string
    {
        $soldierId = $notification->getDeserializedContext()['soldier'] ?? null;
        if ($soldierId === null) {
            return '';
        }

        return $this->urlGenerator->generate('milhq_soldier', ['id' => $soldierId]);
    }

    public function getEmailTemplate(Notification $notification): string
    {
        return '@ForumifyMilhqPlugin/emails/notifications/new_record.html.twig';
    }

    private function getTypeFromContext(Notification $notification)
    {
        return $notification->getDeserializedContext()['type'];
    }
}
