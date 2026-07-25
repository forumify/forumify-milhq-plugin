<?php

declare(strict_types=1);

namespace Forumify\Milhq\Form;

use Forumify\Milhq\Entity\CourseClass;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ClassResultType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CourseClass::class,
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('students', CollectionType::class, [
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'entry_options' => [
                    'course_class' => $options['data'],
                ],
                'entry_type' => ClassStudentResultType::class,
            ])
            ->add('instructors', CollectionType::class, [
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'entry_options' => [
                    'course_class' => $options['data'],
                ],
                'entry_type' => ClassInstructorResultType::class,
            ])
        ;
    }
}
