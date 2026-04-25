<?php

declare(strict_types=1);

namespace Forumify\Milhq\Admin\Form;

use Forumify\Admin\Form\UserRoleType;
use Forumify\Core\Form\RichTextEditorType;
use Forumify\Milhq\Entity\Enum\EquipmentType;
use Forumify\Milhq\Entity\Equipment;
use Forumify\Milhq\Entity\Position;
use Forumify\Milhq\Repository\EquipmentRepository;
use Forumify\Plugin\Service\PluginVersionChecker;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PositionType extends AbstractType
{
    public function __construct(
        private readonly PluginVersionChecker $pluginVersionChecker,
        private readonly EquipmentRepository $equipmentRepository,
    ) {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Position::class,
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class)
            ->add('description', RichTextEditorType::class, [
                'required' => false,
            ])
        ;

        if ($this->pluginVersionChecker->isVersionInstalled('forumify/forumify-milhq-plugin', 'premium')) {
            $builder
                ->add('role', UserRoleType::class, [
                    'placeholder' => 'Do not assign any role',
                    'required' => false,
                ])
                ->add('primaryWeapons', EntityType::class, [
                    'required' => false,
                    'class' => Equipment::class,
                    'multiple' => true,
                    'autocomplete' => true,
                    'choices' => $this->equipmentRepository->findByType(EquipmentType::PrimaryWeapon),
                    'choice_label' => 'name',
                    'help' => "You can assign a weapon or multiple weapons as this position's primary weapon.",
                ])
                ->add('secondaryWeapons', EntityType::class, [
                    'required' => false,
                    'class' => Equipment::class,
                    'multiple' => true,
                    'autocomplete' => true,
                    'choices' => $this->equipmentRepository->findByType(EquipmentType::SecondaryWeapon),
                    'choice_label' => 'name',
                    'help' => "You can assign a weapon or multiple weapons as this position's secondary weapon.",

                ])
            ;
        }
    }
}
