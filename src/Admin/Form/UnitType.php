<?php

declare(strict_types=1);

namespace Forumify\Milhq\Admin\Form;

use Forumify\Admin\Form\UserRoleType;
use Forumify\Core\Form\RichTextEditorType;
use Forumify\Milhq\Entity\Position;
use Forumify\Milhq\Entity\Unit;
use Forumify\Milhq\Repository\PositionRepository;
use Forumify\Plugin\Service\PluginVersionChecker;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UnitType extends AbstractType
{
    public function __construct(private readonly PluginVersionChecker $pluginVersionChecker)
    {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Unit::class,
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class)
            ->add('description', RichTextEditorType::class, [
                'required' => false,
            ])
            ->add('designation', TextType::class, [
                'help' => 'Designation is a identifier for the unit for example: 1st Squad, 2n Platoon, Alpha Company would be 1/2/A-Co',
                'required' => false,
            ])
        ;

        if ($this->pluginVersionChecker->isVersionInstalled('forumify/forumify-milhq-plugin', 'premium')) {
            $builder->add('role', UserRoleType::class, [
                'placeholder' => 'Do not assign any role',
                'required' => false,
            ]);
        }

        $builder
            ->add('supervisors', EntityType::class, [
                'required' => false,
                'multiple' => true,
                'autocomplete' => true,
                'class' => Position::class,
                'choice_label' => 'name',
                'query_builder' => fn(PositionRepository $repository) => $repository
                    ->createQueryBuilder('p')
                    ->orderBy('p.position', 'ASC'),
                'help' => 'Users in these positions will be considered supervisors. If multiple positions are selected, the position\'s sorting will decide the hierarchy.',
            ])
            ->add('markSupervisorsOnRoster', CheckboxType::class, [
                'required' => false,
                'help' => 'When enabled, supervisor positions will have an adornment added to them on the roster.',
            ])
        ;
    }
}
