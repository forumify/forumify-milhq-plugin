<?php

declare(strict_types=1);

namespace Forumify\Milhq\Automation\Trigger;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

class RecordTriggerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('recordType', ChoiceType::class, [
                'choices' => [
                    'Assignment' => 'assignment',
                    'Award' => 'award',
                    'Qualification' => 'qualification',
                    'Rank' => 'rank',
                    'Service' => 'service',
                ],
                'placeholder' => 'All Records',
                'required' => false,
            ])
        ;
    }
}
