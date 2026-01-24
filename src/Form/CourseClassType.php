<?php

declare(strict_types=1);

namespace Forumify\Milhq\Form;

use Forumify\Calendar\Entity\Calendar;
use Forumify\Core\Entity\User;
use Forumify\Core\Form\RichTextEditorType;
use Forumify\Milhq\Entity\CourseClass;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CourseClassType extends AbstractType
{
    public function __construct(private readonly Security $security)
    {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CourseClass::class,
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var User|null $user */
        $user = $this->security->getUser();

        $builder
            ->add('title', TextType::class)
            ->add('description', RichTextEditorType::class, [
                'empty_data' => '',
                'help' => 'milhq.course.class.description_help',
                'required' => false,
            ])
            ->add('signupFrom', DateTimeType::class, [
                'view_timezone' => $user?->getTimezone() ?? 'UTC',
                'widget' => 'single_text',
            ])
            ->add('signupUntil', DateTimeType::class, [
                'view_timezone' => $user?->getTimezone() ?? 'UTC',
                'widget' => 'single_text',
            ])
            ->add('start', DateTimeType::class, [
                'view_timezone' => $user?->getTimezone() ?? 'UTC',
                'widget' => 'single_text',
            ])
            ->add('end', DateTimeType::class, [
                'view_timezone' => $user?->getTimezone() ?? 'UTC',
                'widget' => 'single_text',
            ])
        ;

        if (class_exists(Calendar::class)) {
            $builder->add('calendar', EntityType::class, [
                'autocomplete' => true,
                'choice_label' => 'title',
                'class' => Calendar::class,
                'help' => 'Automatically create a calendar event after posting this class.',
                'placeholder' => 'Do not create a calendar event',
                'required' => false,
            ]);
        }

        $builder
            ->add('studentSlots', NumberType::class, [
                'help' => 'milhq.course.class.student_slots_help',
                'html5' => true,
                'required' => false,
            ])
        ;
    }
}
