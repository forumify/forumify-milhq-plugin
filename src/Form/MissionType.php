<?php

declare(strict_types=1);

namespace Forumify\Milhq\Form;

use Forumify\Calendar\Entity\Calendar;
use Forumify\Core\Entity\User;
use Forumify\Core\Form\RichTextEditorType;
use Forumify\Milhq\Entity\Mission;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MissionType extends AbstractType
{
    public function __construct(private readonly Security $security)
    {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Mission::class,
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var User|null $user */
        $user = $this->security->getUser();

        $builder
            ->add('title')
            ->add('start', DateTimeType::class, [
                'view_timezone' => $user?->getTimezone() ?? 'UTC',
                'widget' => 'single_text',
            ])
            ->add('end', DateTimeType::class, [
                'required' => false,
                'view_timezone' => $user?->getTimezone() ?? 'UTC',
                'widget' => 'single_text',
            ])
        ;

        if (class_exists(Calendar::class)) {
            $builder->add('calendar', EntityType::class, [
                'autocomplete' => true,
                'choice_label' => 'title',
                'class' => Calendar::class,
                'help' => 'Automatically create a calendar event after posting this mission.',
                'placeholder' => 'Do not create a calendar event',
                'required' => false,
            ]);
        }

        $builder
            ->add('sendNotification', CheckboxType::class, [
                'help' => 'Send a "new mission posted" notification to everyone who has access to this operation.',
                'required' => false,
            ])
            ->add('createCombatRecords', CheckboxType::class, [
                'help' => 'Automatically create combat records ',
                'required' => false,
            ])
            ->add('combatRecordText', TextType::class, [
                'help' => 'Only used if create combat records is enabled. If empty, a default message including the operation and mission will be used.',
                'required' => false,
            ])
            ->add('briefing', RichTextEditorType::class)
        ;
    }
}
