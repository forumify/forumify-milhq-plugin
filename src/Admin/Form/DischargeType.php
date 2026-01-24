<?php

declare(strict_types=1);

namespace Forumify\Milhq\Admin\Form;

use Forumify\Milhq\Entity\Position;
use Forumify\Milhq\Entity\Rank;
use Forumify\Milhq\Entity\Status;
use Forumify\Milhq\Entity\Unit;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DischargeType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Discharge::class,
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', ChoiceType::class, [
                'choices' => [
                    'General Discharge' => 'General Discharge',
                    'Honorable Discharge' => 'Honorable Discharge',
                    'Other Than Honorable Discharge' => 'Other Than Honorable Discharge',
                    'Retirement' => 'Retirement',
                ],
            ])
            ->add('reason', TextType::class, [
                'required' => false,
            ])
            ->add('rank', EntityType::class, [
                'choice_label' => 'name',
                'class' => Rank::class,
                'required' => false,
                'placeholder' => 'Remove Rank',
            ])
            ->add('unit', EntityType::class, [
                'choice_label' => 'name',
                'class' => Unit::class,
                'required' => false,
                'placeholder' => 'Remove Unit',
            ])
            ->add('position', EntityType::class, [
                'choice_label' => 'name',
                'class' => Position::class,
                'required' => false,
                'placeholder' => 'Remove Position',
            ])
            ->add('status', EntityType::class, [
                'choice_label' => 'name',
                'class' => Status::class,
                'required' => false,
                'placeholder' => 'Remove Status',
            ]);
    }
}
