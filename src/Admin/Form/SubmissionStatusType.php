<?php

declare(strict_types=1);

namespace Forumify\Milhq\Admin\Form;

use Forumify\Milhq\Entity\Status;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SubmissionStatusType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('status', EntityType::class, [
                'choice_label' => 'name',
                'class' => Status::class,
            ])
            ->add('reason', TextareaType::class, [
                'empty_data' => '',
                'required' => false,
            ])
            ->add('sendNotification', CheckboxType::class, [
                'data' => true,
                'required' => false,
            ]);
    }
}
