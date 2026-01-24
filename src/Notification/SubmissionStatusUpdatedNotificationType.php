<?php

declare(strict_types=1);

namespace Forumify\Milhq\Notification;

use Forumify\Core\Entity\Notification;
use Forumify\Core\Notification\AbstractEmailNotificationType;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class SubmissionStatusUpdatedNotificationType extends AbstractEmailNotificationType
{
    public const TYPE = 'milhq_submission_status_updated';

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
        return $this->translator->trans('milhq.notification.submission_status_updated.title');
    }

    public function getDescription(Notification $notification): string
    {
        $context = $notification->getDeserializedContext();
        return $this->translator->trans('milhq.notification.submission_status_updated.description', [
            'form' => $context['form'],
            'status' => $context['status'],
        ]);
    }

    public function getImage(Notification $notification): string
    {
        return $this->packages->getUrl('bundles/forumifymilhqplugin/images/milhq.png');
    }

    public function getUrl(Notification $notification): string
    {
        $context = $notification->getDeserializedContext();
        return $this->urlGenerator->generate('milhq_operations_center', [
            'submission' => $context['submissionId'],
        ]);
    }

    public function getEmailTemplate(Notification $notification): string
    {
        return '@ForumifyMilhqPlugin/emails/notifications/submission_status_updated.html.twig';
    }
}
