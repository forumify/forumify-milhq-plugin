<?php

declare(strict_types=1);

namespace Forumify\Milhq\Admin\Form;

use Forumify\Core\Form\RichTextEditorType;
use Forumify\Milhq\Entity\Award;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class AwardType extends AbstractType
{
    public function __construct(private readonly Packages $packages)
    {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Award::class,
            'image_required' => false,
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Award|null $data */
        $data = $options['data'] ?? null;
        $imagePreview = $data?->getImage();

        $builder
            ->add('name', TextType::class)
            ->add('description', RichTextEditorType::class, [
                'required' => false,
            ])
            ->add('newAwardImage', FileType::class, [
                'attr' => [
                    'preview' => $imagePreview
                        ? $this->packages->getUrl($imagePreview, 'milhq.asset')
                        : null,
                ],
                'constraints' => [
                    ...($options['image_required'] ? [
                        new Assert\NotBlank(allowNull: false),
                    ] : []),
                    new Assert\Image(
                        maxSize: '1M',
                    ),
                ],
                'help' => 'Recommended size is 250x250.',
                'label' => 'Image',
                'mapped' => false,
            ])
        ;
        if ($data !== null) {
            $builder
                ->add('useTiers', CheckboxType::class, [
                    'required' => false,
                    'mapped' => false,
                    'help' => 'Enable tiers if this award has multiple versions that the soldier can achieve.',
                    'data' => !$data->tiers->isEmpty(),
                ])
                ->add('autoAdvanceTiers', CheckboxType::class, [
                    'required' => false,
                    'help' => 'When enabled, the soldier\'s profile will only show the highest tier achieved. Granting this award multiple times advances the soldier to the next tier.  When disabled, the admin must select a tier when creating the award record, and the soldier can have multiple tiers of the same award.',
                ])
            ;
        }
    }
}
