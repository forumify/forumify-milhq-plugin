<?php

declare(strict_types=1);

namespace Forumify\Milhq\Admin\Form;

use Forumify\Core\Form\EntityType;
use Forumify\Core\Form\RichTextEditorType;
use Forumify\Core\Form\UploadType;
use Forumify\Milhq\Entity\Qualification;
use Forumify\Milhq\Entity\QualificationGroup;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class QualificationType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Qualification::class,
            'group' => null,
        ]);
        $resolver->setAllowedTypes('group', ['null', QualificationGroup::class]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $qualification = $options['data'] ?? null;

        $builder
            ->add('name', TextType::class)
            ->add('group', EntityType::class, [
                'autocomplete' => true,
                'class' => QualificationGroup::class,
                'choice_label' => 'title',
                'placeholder' => 'Ungrouped',
                'help' => 'Optionally organize this qualification into a group.',
                'required' => false,
                ...($qualification === null && $options['group'] !== null
                    ? ['data' => $options['group']]
                    : []),
            ])
            ->add('oldGroupId', HiddenType::class, [
                'data' => (string)$qualification?->getGroup()?->getId(),
                'mapped' => false,
            ])
            ->add('description', RichTextEditorType::class, [
                'required' => false,
            ])
            ->add('image', UploadType::class, [
                'accept' => 'image/*',
                'asset_package' => 'milhq.asset',
                'file_constraints' => [new Assert\Image(maxSize: '1M')],
                'filesystem' => 'milhq_asset.storage',
                'help' => 'Recommended size is 250x250.',
                'label' => 'Image',
                'required' => false,
            ])
        ;
    }
}
