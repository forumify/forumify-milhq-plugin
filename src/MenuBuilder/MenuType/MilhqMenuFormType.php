<?php

declare(strict_types=1);

namespace Forumify\Milhq\MenuBuilder\MenuType;

use Forumify\Plugin\Service\PluginVersionChecker;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;

class MilhqMenuFormType extends AbstractType
{
    public function __construct(private readonly PluginVersionChecker $pluginVersionChecker)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('roster_guests', CheckboxType::class, [
                'label' => 'Show roster to guests',
                'required' => false,
            ])
            ->add('roster_active_duty', CheckboxType::class, [
                'label' => 'Show roster to active duty',
                'required' => false,
            ])
            ->add('awards_guests', CheckboxType::class, [
                'label' => 'Show awards to guests',
                'required' => false,
            ])
            ->add('awards_active_duty', CheckboxType::class, [
                'label' => 'Show awards to active duty',
                'required' => false,
            ])
            ->add('ranks_guests', CheckboxType::class, [
                'label' => 'Show ranks to guests',
                'required' => false,
            ])
            ->add('ranks_active_duty', CheckboxType::class, [
                'label' => 'Show ranks to active duty',
                'required' => false,
            ])
            ->add('qualifications_guests', CheckboxType::class, [
                'label' => 'Show qualifications to guests',
                'required' => false,
            ])
            ->add('qualifications_active_duty', CheckboxType::class, [
                'label' => 'Show qualifications to active duty',
                'required' => false,
            ])
        ;

        if ($this->pluginVersionChecker->isVersionInstalled('forumify/forumify-milhq-plugin', 'premium')) {
            $builder
                ->add('operations_guests', CheckboxType::class, [
                    'label' => 'Show operations to guests',
                    'required' => false,
                ])
                ->add('operations_active_duty', CheckboxType::class, [
                    'label' => 'Show operations to active duty',
                    'required' => false,
                ])
                ->add('courses_guests', CheckboxType::class, [
                    'label' => 'Show courses to guests',
                    'required' => false,
                ])
                ->add('courses_active_duty', CheckboxType::class, [
                    'label' => 'Show courses to active duty',
                    'required' => false,
                ])
            ;
        }
    }
}
