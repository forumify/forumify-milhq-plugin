<?php

declare(strict_types=1);

namespace Forumify\Milhq\Admin\Form;

use Forumify\Admin\Form\UserRoleType;
use Forumify\Core\Form\RichTextEditorType;
use Forumify\Core\Form\UploadType;
use Forumify\Milhq\Entity\Specialty;
use Forumify\Plugin\Service\PluginVersionChecker;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class SpecialtyType extends AbstractType
{
    public function __construct(private readonly PluginVersionChecker $pluginVersionChecker)
    {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Specialty::class,
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class)
            ->add('description', RichTextEditorType::class, [
                'required' => false,
            ])
            ->add('abbreviation', TextType::class, [
                'help' => 'Give this specialty an abbreviation. For example the specialty of a US Infantryman would get the abbreviation of 11B.',
            ])
            ->add('image', UploadType::class, [
                'accept' => 'image/*',
                'asset_package' => 'milhq.asset',
                'file_constraints' => [new Assert\Image(maxSize: '1M')],
                'filesystem' => 'milhq_asset.storage',
                'help' => 'Optional. Shown on the roster next to the soldier. Recommended size is 250x250.',
                'label' => 'Image',
                'required' => false,
            ])
        ;

        if ($this->pluginVersionChecker->isVersionInstalled('forumify/forumify-milhq-plugin', 'premium')) {
            $builder->add('role', UserRoleType::class, [
                'placeholder' => 'Do not assign any role',
                'required' => false,
            ]);
        }
    }
}
