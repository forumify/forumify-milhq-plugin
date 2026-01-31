<?php

declare(strict_types=1);

namespace Forumify\Milhq\EventSubscriber;

use Forumify\Core\Repository\SettingRepository;
use Forumify\Forum\Entity\Topic;
use Forumify\Forum\Form\TopicData;
use Forumify\Forum\Repository\ForumRepository;
use Forumify\Forum\Service\CreateTopicService;
use Forumify\Milhq\Entity\FormField;
use Forumify\Milhq\Entity\FormSubmission;
use Forumify\Milhq\Entity\Soldier;
use Forumify\Milhq\Event\SoldierEnlistedEvent;
use Forumify\Milhq\Repository\SoldierRepository;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
class EnlistListener
{
    public function __construct(
        private readonly SettingRepository $settingRepository,
        private readonly ForumRepository $forumRepository,
        private readonly CreateTopicService $createTopicService,
        private readonly SoldierRepository $userRepository,
    ) {
    }

    public function __invoke(SoldierEnlistedEvent $event): void
    {
        $user = $event->soldier;
        $topic = $this->createEnlistmentTopic($user, $event->submission);
        if ($topic === null) {
            return;
        }

        $user->setEnlistmentTopic($topic);
        $this->userRepository->save($user);
    }

    private function createEnlistmentTopic(Soldier $soldier, FormSubmission $submission): ?Topic
    {
        $forumId = $this->settingRepository->get('milhq.enlistment.forum');
        if (!$forumId) {
            return null;
        }

        $forum = $this->forumRepository->find($forumId);
        if ($forum === null) {
            return null;
        }

        $newTopic = new TopicData();
        $newTopic->setTitle("New enlistment from \"{$soldier->getName()}\"");
        $newTopic->setContent($this->formSubmissionToMarkdown($submission));
        return $this->createTopicService->createTopic($forum, $newTopic);
    }

    private function formSubmissionToMarkdown(FormSubmission $submission): string
    {
        $content = '';

        $data = $submission->getData();
        foreach ($submission->getForm()->getFields() as $field) {
            $label = $field->getLabel();
            $value = $data[$field->getKey()] ?? '';

            $value = match ($field->getType()) {
                'boolean' => $value ? 'Yes' : 'No',
                'date' => (new \DateTime($value))->format('Y-m-d'),
                'datetime' => (new \DateTime($value))->format('Y-m-d H:i:s'),
                'select' => $this->findOptionLabel($value, $field),
                default => $value,
            };

            $content .= "<h5>$label</h5><p>$value</p>";
        }

        return $content;
    }

    private function findOptionLabel(string $value, FormField $field): string
    {
        foreach ($field->fieldOptions['options'] ?? [] as $option) {
            if (($option['key'] ?? null) === $value) {
                return $option['label'] ?? '';
            }
        }
        return '';
    }
}
