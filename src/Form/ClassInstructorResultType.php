<?php

declare(strict_types=1);

namespace Forumify\Milhq\Form;

use Forumify\Milhq\Entity\CourseClass;
use Forumify\Milhq\Entity\CourseClassInstructor;
use Forumify\Milhq\Entity\CourseInstructor;
use Forumify\Milhq\Entity\Soldier;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ClassInstructorResultType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'course_class' => null,
            'data_class' => CourseClassInstructor::class,
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var CourseClass $class */
        $class = $options['course_class'];

        $builder
            ->add('soldier', EntityType::class, [
                'attr' => ['class' => 'd-none'],
                'autocomplete' => true,
                'choice_label' => 'name',
                'class' => Soldier::class,
                'label' => false,
                'placeholder' => 'Please select a soldier',
            ])
            ->add('present', CheckboxType::class, [
                'required' => false,
            ])
            ->add('instructor', EntityType::class, [
                'autocomplete' => true,
                'choices' => $class->getCourse()->getInstructors(),
                'choice_label' => 'title',
                'class' => CourseInstructor::class,
                'label' => 'Role',
                'required' => false,
            ])
        ;
    }
}
