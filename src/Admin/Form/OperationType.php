<?php

declare(strict_types=1);

namespace Forumify\Milhq\Admin\Form;

use Forumify\Core\Form\RichTextEditorType;
use Forumify\Core\Form\UploadType;
use Forumify\Milhq\Entity\Operation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class OperationType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Operation::class,
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title')
            ->add('description', TextareaType::class, [
                'help' => 'milhq.admin.operation.description_help',
            ])
            ->add('image', UploadType::class, [
                'accept' => 'image/*',
                'asset_package' => 'milhq.asset',
                'file_constraints' => [new Assert\Image(maxSize: '1M')],
                'filesystem' => 'milhq_asset.storage',
                'label' => 'Image',
                'required' => false,
            ])
            ->add('content', RichTextEditorType::class, [
                'empty_data' => '',
                'required' => false,
            ])
            ->add('start', DateType::class, [
                'required' => false,
                'widget' => 'single_text',
            ])
            ->add('end', DateType::class, [
                'required' => false,
                'widget' => 'single_text',
            ])
            ->add('requestRsvp', CheckboxType::class, [
                'help' => 'milhq.admin.operation.rsvp_help',
                'required' => false,
            ])
            ->add('missionBriefingTemplate', RichTextEditorType::class, [
                'empty_data' => '',
                'required' => false,
            ])
            ->add('afterActionReportTemplate', RichTextEditorType::class, [
                'empty_data' => '',
                'required' => false,
            ]);
    }
}
