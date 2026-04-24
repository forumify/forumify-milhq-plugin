<?php

namespace Forumify\Milhq\Admin\Form;

use Forumify\Milhq\Entity\Equipment;
use Forumify\Milhq\Entity\Enum\EquipmentType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EquipmentFormType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Equipment::class,
        ]);
    }

    public function buildform(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class)
            ->add('type', EnumType::class, [
                'class' => EquipmentType::class,
            ])
        ;
    }
}
