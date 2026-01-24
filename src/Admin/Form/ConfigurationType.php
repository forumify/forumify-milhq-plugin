<?php

declare(strict_types=1);

namespace Forumify\Milhq\Admin\Form;

use Forumify\Core\Form\RichTextEditorType;
use Forumify\Core\Repository\RoleRepository;
use Forumify\Forum\Repository\ForumRepository;
use Forumify\Milhq\Repository\FormRepository;
use Forumify\Milhq\Repository\StatusRepository;
use Forumify\Plugin\Service\PluginVersionChecker;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

class ConfigurationType extends AbstractType
{
    private ?array $statusChoices = null;

    public function __construct(
        private readonly ForumRepository $forumRepository,
        private readonly StatusRepository $statusRepository,
        private readonly FormRepository $formRepository,
        private readonly RoleRepository $roleRepository,
        private readonly PluginVersionChecker $pluginVersionChecker,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // Operations Center
            ->add('milhq__opcenter__announcement', RichTextEditorType::class, [
                'help' => 'Content to show on the operations center.',
                'label' => 'Announcement',
                'required' => false,
            ])
            // Roster
            ->add('milhq__roster__user_sort_order', TextType::class, [
                'help' => 'Comma separated order used when sorting users. Available options: "rank", "position" and "specialty". Default: "rank, position, specialty". The user\'s name is used as tie-breaker.',
                'label' => 'User Sort Order',
                'required' => false,
                'attr' => [
                    'placeholder' => 'rank, position, specialty, name',
                ],
            ])
            // Enlistment
            ->add('milhq__enlistment__status', ChoiceType::class, [
                'autocomplete' => true,
                'choices' => $this->getStatusChoices(),
                'help' => 'Only soldiers in these statuses can start the enlistment process. Soldiers that have no status, or forum users that don\'t have a soldier will always be allowed to enlist.',
                'label' => 'Eligible Status',
                'multiple' => true,
                'required' => false,
            ])
            ->add('milhq__enlistment__form', ChoiceType::class, [
                'autocomplete' => true,
                'choices' => $this->getFormChoices(),
                'help' => 'The form to use for enlistments, by default, all required fields to create a soldier are already added by this plugin.',
                'label' => 'Enlistment Form',
                'placeholder' => 'Select a form to use for enlistments',
                'required' => false,
            ])
            ->add('milhq__enlistment__roleplay_names', CheckboxType::class, [
                'required' => false,
                'label' => 'Require roleplay friendly name',
                'help' => 'When enabled, the enlistment form will show "firstname" and "lastname". Otherwise, it will use the logged in user\'s forum display name.',
            ])
            ->add('milhq__enlistment__forum', ChoiceType::class, [
                'autocomplete' => true,
                'choices' => $this->getForumChoices(),
                'help' => 'Automatically post a topic containing the enlistment to this forum.',
                'label' => 'Enlistment Forum',
                'placeholder' => 'Do not create enlistment topics',
                'required' => false,
            ])
            ->add('milhq__enlistment__role', ChoiceType::class, [
                'autocomplete' => true,
                'choices' => $this->getRoleChoices(),
                'help' => 'Automatically assign this role to the user upon creating an enlistment.',
                'label' => 'Enlistee Role',
                'placeholder' => 'Do not assign a role',
                'required' => false,
            ])
            // Profiles
            ->add('milhq__profile__overwrite_display_names', CheckboxType::class, [
                'help' => 'Automatically set a user\'s forum display name to match their soldier name.',
                'label' => 'Overwrite user display names',
                'required' => false,
            ])
            ->add('milhq__profile__display_name_format', TextType::class, [
                'empty_data' => '{user.rank.abbreviation} {user.name}',
                'help' => 'milhq.settings.profile.display_name_format_help',
                'help_html' => true,
                'label' => 'Display name format',
                'required' => false,
            ])
            ->add('milhq__profile__overwrite_avatars', CheckboxType::class, [
                'help' => 'Automatically set the user\'s avatar to match their soldier rank.',
                'label' => 'Overwrite user avatar',
                'required' => false,
            ])
            ->add('milhq__profile__overwrite_signatures', CheckboxType::class, [
                'help' => 'Automatically set the user\'s signature to match their soldier signature.',
                'label' => 'Overwrite user signature',
                'required' => false,
            ])
        ;

        if ($this->pluginVersionChecker->isVersionInstalled('forumify/forumify-milhq-plugin', 'premium')) {
            $builder
                // Reporting In
                ->add('milhq__report_in__enabled', CheckboxType::class, [
                    'help' => 'Enforces users to press the "Report In" button on the operations center at least once every x days',
                    'label' => 'Enabled',
                    'required' => false,
                ])
                ->add('milhq__report_in__enabled_status', ChoiceType::class, [
                    'autocomplete' => true,
                    'choices' => $this->getStatusChoices(),
                    'help' => 'Which statuses need to be checked for report in activity?',
                    'label' => 'Enabled status',
                    'multiple' => true,
                    'required' => false,
                ])
                ->add('milhq__report_in__period', NumberType::class, [
                    'help' => 'If a user hasn\'t reported in during this time, their status will be changed.',
                    'html5' => true,
                    'label' => 'Period (in days)',
                    'required' => false,
                    'scale' => 0,
                ])
                ->add('milhq__report_in__warning_period', NumberType::class, [
                    'help' => 'If a user hasn\'t reported in during this time, a warning notification will be sent. Leave blank to disable warnings.',
                    'html5' => true,
                    'label' => 'Warning Period (in days)',
                    'required' => false,
                    'scale' => 0,
                ])
                ->add('milhq__report_in__failure_status', ChoiceType::class, [
                    'autocomplete' => true,
                    'choices' => $this->getStatusChoices(),
                    'help' => 'Status to move the user to when they fail to report in. For example: AWOL',
                    'label' => 'Failure status',
                    'required' => false,
                ])
                // Operations
                ->add('milhq__operations__absent_notification', CheckboxType::class, [
                    'help' => 'Send a simple notification when the user is marked absent in an after action report.',
                    'label' => 'Send absent notifications',
                    'required' => false,
                ])
                ->add('milhq__operations__absent_notification_message', TextType::class, [
                    'constraints' => [new Assert\Length(max: 300)],
                    'help' => 'If absent notifications are turned on, and this field is empty, a standard message will be used. Max 300 characters.',
                    'label' => 'Absent notification message',
                    'required' => false,
                ])
                ->add('milhq__operations__consecutive_absent_notification', CheckboxType::class, [
                    'help' => 'Send an email and optionally change the user\'s status when they are marked absent multiple times in a row.',
                    'label' => 'Send consecutive absence notifications',
                    'required' => false,
                ])
                ->add('milhq__operations__consecutive_absent_notification_count', NumberType::class, [
                    'constraints' => [new Assert\PositiveOrZero()],
                    'help' => 'How many times the user needs to be marked absent in an after action report before being considered consecutively absent. For example, if set to 3, the user will receive their first notification on their third absence.',
                    'label' => 'Consecutive absence count',
                    'required' => false,
                ])
                ->add('milhq__operations__consecutive_absent_notification_message', RichTextEditorType::class, [
                    'help' => 'If this is empty, a standard message will be used.',
                    'label' => 'Consecutive absence email content',
                    'required' => false,
                ])
                ->add('milhq__operations__consecutive_absent_status', ChoiceType::class, [
                    'autocomplete' => true,
                    'choices' => $this->getStatusChoices(),
                    'help' => 'Automatically move users with consecutive absences to a different status.',
                    'label' => 'Consecutive absence status',
                    'placeholder' => 'Do not change status',
                    'required' => false,
                ])
                // SquadXML
                ->add('milhq__squadxml__enabled', CheckboxType::class, [
                    'label' => 'Enabled',
                    'required' => false,
                ])
                ->add('milhq__squadxml__name', TextType::class, [
                    'constraints' => [new Assert\Length(max: 64), new Assert\Regex('/^[^<>]+$/')],
                    'help' => 'Unit name, leave blank to use the website name.',
                    'label' => 'Name',
                    'required' => false,
                ])
                ->add('milhq__squadxml__title', TextType::class, [
                    'constraints' => [new Assert\Length(max: 64), new Assert\Regex('/^[^<>]+$/')],
                    'help' => 'Unit title, shown on vehicles etc. Leave blank to use the same as name.',
                    'label' => 'Name',
                    'required' => false,
                ])
                ->add('milhq__squadxml__nick', TextType::class, [
                    'constraints' => [new Assert\Length(max: 64), new Assert\Regex('/^[a-zA-Z0-9]+$/')],
                    'help' => 'Also known as your clan tag. Only characters A-z and 0-9 are allowed. So if your tag is "-=[TAG]=-", use "TAG" instead.',
                    'label' => 'Nick',
                    'required' => false,
                ])
                ->add('milhq__squadxml__email', TextType::class, [
                    'constraints' => [new Assert\Email(), new Assert\Length(max: 64), new Assert\Regex('/^[^<>]+$/')],
                    'help' => 'Not required.',
                    'label' => 'Contact email',
                    'required' => false,
                ])
                ->add('milhq__squadxml__web', TextType::class, [
                    'constraints' => [new Assert\Length(max: 64), new Assert\Regex('/^[^<>]+$/')],
                    'help' => 'Only required if it is different from this forumify website. Leave blank to use the website you are on right now.',
                    'label' => 'Website',
                    'required' => false,
                ])
                ->add('milhq__squadxml__new_picture', FileType::class, [
                    'constraints' => [new Assert\File(maxSize: '1M')],
                    'help' => 'Logo in PAA format. Don\'t know how to create a PAA file? See <a href="https://community.bistudio.com/wiki/squad.xml#Logo_Creation">official documentation</a>, or use this <a href="https://paa.gruppe-adler.de/">online converter</a>.',
                    'help_html' => true,
                    'label' => 'Logo (.paa)',
                    'required' => false,
                ])
            ;
        }
    }

    private function getForumChoices(): array
    {
        $choices = $this->forumRepository
            ->createQueryBuilder('f')
            ->select('f.id', 'f.title')
            ->getQuery()
            ->getArrayResult();

        return array_combine(
            array_column($choices, 'title'),
            array_column($choices, 'id'),
        );
    }

    private function getRoleChoices(): array
    {
        $choices = $this->roleRepository
            ->createQueryBuilder('r')
            ->select('r.id', 'r.title')
            ->getQuery()
            ->getArrayResult();

        return array_combine(
            array_column($choices, 'title'),
            array_column($choices, 'id'),
        );
    }

    private function getStatusChoices(): array
    {
        if ($this->statusChoices !== null) {
            return $this->statusChoices;
        }

        $choices = $this->statusRepository
            ->createQueryBuilder('s')
            ->select('s.id', 's.name')
            ->orderBy('s.position', 'ASC')
            ->getQuery()
            ->getArrayResult();

        $this->statusChoices = array_combine(
            array_column($choices, 'name'),
            array_column($choices, 'id'),
        );
        return $this->statusChoices;
    }

    private function getFormChoices(): array
    {
        $choices = $this->formRepository
            ->createQueryBuilder('f')
            ->select('f.id', 'f.name')
            ->getQuery()
            ->getArrayResult();

        return array_combine(
            array_column($choices, 'name'),
            array_column($choices, 'id'),
        );
    }
}
