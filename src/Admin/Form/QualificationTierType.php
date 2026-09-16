<?php

declare(strict_types=1);

namespace Forumify\Milhq\Admin\Form;

use Forumify\Core\Form\UploadType;
use Forumify\Milhq\Entity\QualificationTier;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class QualificationTierType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => QualificationTier::class,
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class)
            ->add('image', UploadType::class, [
                'accept' => 'image/*',
                'asset_package' => 'milhq.asset',
                'file_constraints' => [new Assert\Image(maxSize: '1M')],
                'filesystem' => 'milhq_asset.storage',
                'label' => 'Image',
                'required' => false,
            ])
        ;
    }
}
