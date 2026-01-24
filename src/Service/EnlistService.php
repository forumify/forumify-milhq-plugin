<?php

declare(strict_types=1);

namespace Forumify\Milhq\Service;

use Forumify\Core\Entity\User;
use Forumify\Core\Repository\SettingRepository;
use Forumify\Milhq\Form\Enlistment;
use Forumify\Milhq\Entity\Form;
use Forumify\Milhq\Entity\FormSubmission;
use Forumify\Milhq\Entity\Soldier;
use Forumify\Milhq\Event\SoldierEnlistedEvent;
use Forumify\Milhq\Repository\FormRepository;
use Forumify\Milhq\Repository\FormSubmissionRepository;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class EnlistService
{
    public function __construct(
        private readonly SoldierService $soldierService,
        private readonly SettingRepository $settingRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly FormRepository $formRepository,
        private readonly FormSubmissionRepository $formSubmissionRepository,
    ) {
    }

    public function canEnlist(?User $user = null): bool
    {
        $soldier = $user === null
            ? $this->soldierService->getLoggedInSoldier()
            : $this->soldierService->getSoldier($user);

        if ($soldier === null) {
            return true;
        }

        $allowedEnlistmentStatuses = $this->settingRepository->get('milhq.enlistment.status') ?? [];
        $statusId = $soldier->getStatus()?->getId();
        return $statusId === null || in_array($statusId, $allowedEnlistmentStatuses, true);
    }

    public function getEnlistmentForm(): ?Form
    {
        $formId = $this->settingRepository->get('milhq.enlistment.form');
        if ($formId === null) {
            return null;
        }

        return $this->formRepository->find($formId);
    }

    public function enlist(Enlistment $enlistment): Soldier
    {
        $soldier = $this->soldierService->getLoggedInSoldier()
            ?? $this->soldierService->createUser($enlistment);

        $submission = new FormSubmission();
        $submission->setForm($this->getEnlistmentForm());
        $submission->setSoldier($soldier);
        $submission->setData($enlistment->additionalFormData);
        $this->formSubmissionRepository->save($submission);

        $this->eventDispatcher->dispatch(new SoldierEnlistedEvent($soldier, $submission));
        return $soldier;
    }
}
