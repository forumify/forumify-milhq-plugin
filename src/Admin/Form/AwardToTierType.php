<?php

declare(strict_types=1);

namespace Forumify\Milhq\Admin\Form;

use Forumify\Core\Form\EntityType;
use Forumify\Milhq\Entity\Award;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * @extends AbstractType<array{
 *     award: string,
 *     tierName: string,
 *     targetAward: Award,
 *     targetAwardName: string|null,
 *     targetTierName: string|null,
 * }>
 */
class AwardToTierType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('award', TextType::class, [
                'disabled' => true,
            ])
            ->add('tierName', TextType::class, [
                'help' => 'The name of the tier to be created, e.g.: if the award above is named "Combat Infantry Badge - Stage 1", you would fill in "Stage 1" here.',
            ])
            ->add('targetAward', EntityType::class, [
                'class' => Award::class,
                'autocomplete' => true,
                'choice_label' => 'name',
                'help' => 'The award that will receive the new tier.',
            ])
            ->add('targetAwardName', TextType::class, [
                'required' => false,
                'help' => 'Optionally rename the target award. e.g.: if your target is named "Marksmanship (Expert)", you can use this field to rename it to "Marksmanship". Leave blank to keep current name.',
            ])
            ->add('targetTierName', TextType::class, [
                'required' => false,
                'help' => 'Optionally rename the target tier. This option only applies if we need to turn the target award into a tier of itself, this only happens if the target award has any records associated with it already.  e.g.: if your target is named "Marksmanship (Expert)", you can use this field to rename it to "Expert". Leave blank to use the award name as tier name.',
            ])
        ;
    }
}
