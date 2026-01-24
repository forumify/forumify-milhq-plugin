<?php

declare(strict_types=1);

namespace Forumify\Milhq\Admin\Form;

use Forumify\Milhq\Entity\Award;
use Forumify\Milhq\Entity\Document;
use Forumify\Milhq\Entity\Soldier;
use Forumify\Milhq\Entity\Position;
use Forumify\Milhq\Entity\Qualification;
use Forumify\Milhq\Entity\Rank;
use Forumify\Milhq\Entity\Specialty;
use Forumify\Milhq\Entity\Status;
use Forumify\Milhq\Entity\Unit;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RecordType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'type' => 'service',
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('users', EntityType::class, [
                'autocomplete' => true,
                'choice_label' => 'name',
                'class' => Soldier::class,
                'multiple' => true,
            ])
            ->add('created_at', DateTimeType::class, [
                'required' => false,
                'widget' => 'single_text',
            ]);

        switch ($options['type']) {
            case 'award':
                $this->addAwardFields($builder);
                break;
            case 'rank':
                $this->addRankFields($builder);
                break;
            case 'assignment':
                $this->addAssignmentFields($builder);
                break;
            case 'qualification':
                $this->addQualificationFields($builder);
                break;
            default:
                // no-op
        }

        $builder
            ->add('text', TextareaType::class, [
                'empty_data' => '',
                'required' => false,
            ])
            ->add('document', EntityType::class, [
                'autocomplete' => true,
                'choice_label' => 'name',
                'class' => Document::class,
                'required' => false,
            ])
            ->add('sendNotification', CheckboxType::class, [
                'data' => true,
                'required' => false,
            ]);
    }

    private function addAwardFields(FormBuilderInterface $builder): void
    {
        $builder->add('award', EntityType::class, [
            'autocomplete' => true,
            'choice_label' => 'name',
            'class' => Award::class,
        ]);
    }

    private function addRankFields(FormBuilderInterface $builder): void
    {
        $builder
            ->add('type', ChoiceType::class, [
                // phpcs:ignore
                'choices' => [
                    'Promote' => 'promotion',
                    'Demote' => 'demotion',
                ],
            ])
            ->add('rank', EntityType::class, [
                'autocomplete' => true,
                'choice_label' => 'name',
                'class'=> Rank::class,
            ]);
    }

    private function addAssignmentFields(FormBuilderInterface $builder): void
    {
        $builder
            ->add('type', ChoiceType::class, [
                'choices' => [
                    'Primary' => 'primary',
                    'Secondary' => 'secondary',
                ],
                'placeholder' => 'Select a type',
            ])
            ->add('status', EntityType::class, [
                'autocomplete' => true,
                'choice_label' => 'name',
                'class' => Status::class,
                'placeholder' => 'Keep current status.',
                'required' => false,
            ])
            ->add('specialty', EntityType::class, [
                'autocomplete' => true,
                'choice_label' => 'name',
                'class' => Specialty::class,
                'placeholder' => 'Keep current specialty.',
                'required' => false,
            ])
            ->add('unit', EntityType::class, [
                'autocomplete' => true,
                'choice_label' => 'name',
                'class' => Unit::class,
            ])
            ->add('position', EntityType::class, [
                'autocomplete' => true,
                'choice_label' => 'name',
                'class' => Position::class,
            ]);
    }

    private function addQualificationFields(FormBuilderInterface $builder): void
    {
        $builder->add('qualification', EntityType::class, [
            'autocomplete' => true,
            'choice_label' => 'name',
            'class' => Qualification::class,
        ]);
    }
}
