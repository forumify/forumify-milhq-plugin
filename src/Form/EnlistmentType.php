<?php

declare(strict_types=1);

namespace Forumify\Milhq\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class EnlistmentType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Enlistment::class,
            'form' => null,
            'roleplay_names' => false,
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($options['roleplay_names']) {
            $builder
                ->add('firstName', TextType::class, [
                    'constraints' => [
                        new NotBlank(),
                        new Length(min: 2),
                    ],
                ])
                ->add('lastName', TextType::class, [
                    'constraints' => [
                        new NotBlank(),
                        new Length(min: 2),
                    ],
                ])
            ;
        }

        $builder->add('additionalFormData', SubmissionFormType::class, [
            'label' => false,
            'submissionForm' => $options['form'],
        ]);
    }
}
