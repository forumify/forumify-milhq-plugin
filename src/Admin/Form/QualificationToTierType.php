<?php

declare(strict_types=1);

namespace Forumify\Milhq\Admin\Form;

use Forumify\Core\Form\EntityType;
use Forumify\Milhq\Entity\Qualification;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * @extends AbstractType<array{ targetQualification: Qualification, rename: string|null }>
 */
class QualificationToTierType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('qualification', TextType::class, [
                'disabled' => true,
            ])
            ->add('tierName', TextType::class, [
                'help' => 'The name of the tier to be created, e.g.: if the qualification above is named "Qualified with M4A1 (Expert)", you would fill in "Expert" here.',
            ])
            ->add('targetQualification', EntityType::class, [
                'class' => Qualification::class,
                'autocomplete' => true,
                'choice_label' => 'name',
                'help' => 'The qualification that will receive the new tier.',
            ])
            ->add('targetQualificationName', TextType::class, [
                'required' => false,
                'help' => 'Optionally rename the target qualification. e.g.: if your target is named "Qualified with M4A1 (Expert)", you can use this field to rename it to "Qualified with M4A1". Leave blank to keep current name.',
            ])
            ->add('targetTierName', TextType::class, [
                'required' => false,
                'help' => 'Optionally rename the target tier. This option only applies if we need to turn the target qualification into a tier of itself, this only happens if the target qualification has any records associated with it already.  e.g.: if your target is named "Qualified with M4A1 (Expert)", you can use this field to rename it to "Expert". Leave blank to use the qualification name as tier name.',
            ])
        ;
    }
}
