<?php

declare(strict_types=1);

namespace Forumify\Milhq\Admin\Form;

use Forumify\Core\Form\EntityType;
use Forumify\Core\Form\RichTextEditorType;
use Forumify\Core\Form\UploadType;
use Forumify\Milhq\Entity\Award;
use Forumify\Milhq\Entity\AwardGroup;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class AwardType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Award::class,
            'image_required' => false,
            'group' => null,
        ]);
        $resolver->setAllowedTypes('group', ['null', AwardGroup::class]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $award = $options['data'] ?? null;

        $builder
            ->add('name', TextType::class)
            ->add('group', EntityType::class, [
                'autocomplete' => true,
                'class' => AwardGroup::class,
                'choice_label' => 'title',
                'placeholder' => 'Ungrouped',
                'help' => 'Optionally organize this award into a group.',
                'required' => false,
                ...($award === null && $options['group'] !== null
                    ? ['data' => $options['group']]
                    : []),
            ])
            ->add('oldGroupId', HiddenType::class, [
                'data' => (string)$award?->getGroup()?->getId(),
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
                'required' => $options['image_required'],
            ])
            ->add('autoAdvanceTiers', CheckboxType::class, [
                'required' => false,
                'help' => 'When enabled, the soldier\'s profile will only show the highest tier achieved. Granting this award multiple times advances the soldier to the next tier.  When disabled, the admin must select a tier when creating the award record, and the soldier can have multiple tiers of the same award.',
            ])
        ;
    }
}
