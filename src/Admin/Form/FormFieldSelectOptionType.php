<?php

declare(strict_types=1);

namespace Forumify\Milhq\Admin\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class FormFieldSelectOptionType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('label', false);
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('key', TextType::class, [
                'constraints' => new NotBlank(allowNull: false),
                'label' => false,
            ])
            ->add('label', TextType::class, [
                'constraints' => new NotBlank(allowNull: false),
                'label' => false,
            ])
        ;
    }
}
