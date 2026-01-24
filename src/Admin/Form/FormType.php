<?php

declare(strict_types=1);

namespace Forumify\Milhq\Admin\Form;

use Forumify\Core\Form\RichTextEditorType;
use Forumify\Milhq\Entity\Form;
use Forumify\Milhq\Entity\Status;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FormType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Form::class,
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class)
            ->add('description', TextareaType::class, [
                'empty_data' => '',
                'help' => 'An internal description shown to administrators.',
                'required' => false,
            ])
            ->add('instructions', RichTextEditorType::class, [
                'empty_data' => '',
                'help' => 'A message that is displayed to the user when opening the form.',
                'required' => false,
            ])
            ->add('defaultStatus', EntityType::class, [
                'choice_label' => 'name',
                'class' => Status::class,
                'help' => 'Status to assign to the form submission by default. For example "Pending".',
                'required' => false,
            ])
            ->add('successMessage', RichTextEditorType::class, [
                'empty_data' => '',
                'help' => 'A message that is displayed after the user submitted the form.',
                'required' => false,
            ])
        ;
    }
}
