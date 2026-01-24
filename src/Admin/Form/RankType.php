<?php

declare(strict_types=1);

namespace Forumify\Milhq\Admin\Form;

use Forumify\Core\Form\RichTextEditorType;
use Forumify\Milhq\Entity\Rank;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class RankType extends AbstractType
{
    public function __construct(private readonly Packages $packages)
    {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Rank::class,
            'image_required' => false,
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $imagePreview = empty($options['data']) ? null : $options['data']->getImage();

        $builder
            ->add('name', TextType::class)
            ->add('description', RichTextEditorType::class, [
                'required' => false,
            ])
            ->add('abbreviation', TextType::class, [
                'help' => 'Add an abbreviation to the rank; e.g., PFC',
                'required' => false,
            ])
            ->add('paygrade', TextType::class, [
                'help' => 'Add a paygrade to the rank; e.g., PV1 = OR-1 (NATO) or E-1 (US Army)',
                'required' => false,
            ])
            ->add('newImage', FileType::class, [
                'attr' => [
                    'preview' => $imagePreview
                        ? $this->packages->getUrl($imagePreview, 'milhq.asset')
                        : null,
                ],
                'constraints' => [
                    ...($options['image_required'] ? [
                        new Assert\NotBlank(allowNull: false),
                    ]: []),
                    new Assert\Image(
                        maxSize: '1M',
                    ),
                ],
                'help' => 'Recommended size is 250x250.',
                'label' => 'Image',
                'mapped' => false,
            ])
        ;
    }
}
