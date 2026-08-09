<?php

declare(strict_types=1);

namespace Forumify\Milhq\Components;

use Forumify\Core\Form\EntityType;
use Forumify\Core\Security\VoterAttribute;
use Forumify\Milhq\Admin\Service\SubmissionStatusUpdateService;
use Forumify\Milhq\Entity\FormSubmission;
use Forumify\Milhq\Entity\Status;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent('Milhq\\SubmissionStatusForm', '@ForumifyMilhqPlugin/frontend/components/submission_status_form.html.twig')]
class SubmissionStatusForm extends AbstractController
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;
    use ComponentToolsTrait;

    #[LiveProp]
    public FormSubmission $submission;

    public bool $success = false;

    public function __construct(
        private readonly SubmissionStatusUpdateService $submissionStatusUpdateService,
    ) {
    }

    protected function instantiateForm(): FormInterface
    {
        return $this->createFormBuilder()
            ->add('status', EntityType::class, [
                'choice_label' => 'name',
                'class' => Status::class,
            ])
            ->add('reason', TextareaType::class, [
                'empty_data' => '',
                'required' => false,
            ])
            ->getForm()
        ;
    }

    // @phpstan-ignore-next-line
    private function getDataModelValue(): ?string
    {
        return 'norender|*';
    }

    #[LiveAction]
    public function save(): void
    {
        if (!$this->isGranted(VoterAttribute::ACL->value, [
            'permission' => 'supervisor_manage_submissions',
            'entity' => $this->submission->getForm(),
        ])) {
            return;
        }

        $this->submitForm();

        $statusRecord = $this->getForm()->getData();
        $statusRecord['sendNotification'] = true;
        $this->submissionStatusUpdateService->createStatusRecord($this->submission, $statusRecord);

        $this->success = true;
        $this->resetForm();

        $this->emitUp('milhq:submission:status_updated');
    }
}
