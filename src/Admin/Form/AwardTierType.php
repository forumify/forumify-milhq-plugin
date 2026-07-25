<?php

declare(strict_types=1);

namespace Forumify\Milhq\Admin\Form;

use Forumify\Milhq\Entity\AwardTier;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class AwardTierType extends AbstractType
{
    public function __construct(private readonly Packages $packages)
    {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AwardTier::class,
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $imagePreview = empty($options['data']) ? null : $options['data']->image;

        $builder
            ->add('name', TextType::class)
            ->add('newImage', FileType::class, [
                'attr' => [
                    'preview' => $imagePreview
                        ? $this->packages->getUrl($imagePreview, 'milhq.asset')
                        : null,
                ],
                'constraints' => [
                    new Assert\Image(
                        maxSize: '1M',
                    ),
                ],
                'required' => false,
                'label' => 'Image',
                'mapped' => false,
            ])
        ;
    }
}
