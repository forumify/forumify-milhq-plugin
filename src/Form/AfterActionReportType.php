<?php

declare(strict_types=1);

namespace Forumify\Milhq\Form;

use Forumify\Core\Form\RichTextEditorType;
use Forumify\Milhq\Entity\AfterActionReport;
use Forumify\Milhq\Entity\Unit;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AfterActionReportType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'allow_extra_fields' => true,
            'data_class' => AfterActionReport::class,
            'is_new' => true,
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isNew = $options['is_new'];
        $attendance = !$isNew
            ? json_encode($options['data']->getAttendance(), JSON_THROW_ON_ERROR)
            : null;

        $builder
            ->add('unit', EntityType::class, [
                'autocomplete' => true,
                'choice_label' => 'name',
                'class' => Unit::class,
                'disabled' => !$isNew,
                'label' => 'Unit',
            ])
            ->add('attendanceJson', HiddenType::class, [
                'data' => $attendance,
                'mapped' => false,
            ])
            ->add('report', RichTextEditorType::class);
    }
}
