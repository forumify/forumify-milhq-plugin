<?php

declare(strict_types=1);

namespace Forumify\Milhq\Admin\Form;

use Doctrine\ORM\EntityRepository;
use Forumify\Core\Form\EntityType;
use Forumify\Milhq\Entity\Form;
use Forumify\Milhq\Entity\FormStatus;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SubmissionStatusType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('form');
        $resolver->setAllowedTypes('form', Form::class);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('status', EntityType::class, [
                'choice_label' => 'name',
                'class' => FormStatus::class,
                'query_builder' => fn (EntityRepository $repository) => $repository
                    ->createQueryBuilder('fs')
                    ->where('fs.form = :form')
                    ->setParameter('form', $options['form']),
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
