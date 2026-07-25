<?php

declare(strict_types=1);

namespace Forumify\Milhq\Form;

use Forumify\Core\Form\RichTextEditorType;
use Forumify\Milhq\Entity\Award;
use Forumify\Milhq\Entity\CourseClass;
use Forumify\Milhq\Entity\CourseClassStudent;
use Forumify\Milhq\Entity\Enum\CourseResult;
use Forumify\Milhq\Entity\Qualification;
use Forumify\Milhq\Entity\Soldier;
use Forumify\Milhq\Repository\AwardRepository;
use Forumify\Milhq\Repository\QualificationRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @phpstan-type Achievement array{name: string, kind: string, entityId: int, tiered: bool}
 */
class ClassStudentResultType extends AbstractType
{
    public function __construct(
        private readonly QualificationRepository $qualificationRepository,
        private readonly AwardRepository $awardRepository,
    ) {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'course_class' => null,
            'data_class' => CourseClassStudent::class,
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var CourseClass $class */
        $class = $options['course_class'];
        $course = $class->getCourse();

        $builder
            ->add('soldier', EntityType::class, [
                'autocomplete' => true,
                'choice_label' => 'name',
                'class' => Soldier::class,
                'label' => false,
                'placeholder' => 'Please select a soldier',
            ])
            ->add('result', EnumType::class, [
                'attr' => [
                    'data-role' => 'result',
                ],
                'choice_label' => fn (CourseResult $result) => 'milhq.course.class.result_option.' . $result->value,
                'class' => CourseResult::class,
                'required' => false,
            ]);

        $achievements = [];
        if ($qualifications = $course->getQualifications()) {
            foreach ($this->qualificationRepository->findBy(['id' => $qualifications]) as $qualification) {
                $achievements[] = $this->addAchievementField($builder, $qualification);
            }
        }

        if ($awards = $course->getAwards()) {
            foreach ($this->awardRepository->findBy(['id' => $awards]) as $award) {
                $achievements[] = $this->addAchievementField($builder, $award);
            }
        }

        $builder
            ->add('serviceRecordTextOverride', TextType::class, [
                'label' => 'milhq.course.class.service_record_override',
                'required' => false,
            ])
            ->add('notes', RichTextEditorType::class, [
                'empty_data' => '',
                'label' => 'milhq.course.class.notes',
                'required' => false,
            ]);

        $builder->addEventListener(FormEvents::POST_SET_DATA, fn(FormEvent $event) => $this->populateCells($event, $achievements));
        $builder->addEventListener(FormEvents::POST_SUBMIT, fn(FormEvent $event) => $this->applyCells($event, $achievements));
    }

    /**
     * @return Achievement
     */
    private function addAchievementField(FormBuilderInterface $builder, Award|Qualification $achievement): array
    {
        $kind = $achievement instanceof Award ? 'award' : 'qualification';

        $id = $achievement->getId();
        $name = $kind . '_' . $achievement->getId();

        $tiers = $achievement->tiers;
        $tiered = !$tiers->isEmpty() && !($achievement instanceof Award && $achievement->autoAdvanceTiers);

        if ($tiered) {
            $choices = [];
            foreach ($tiers as $tier) {
                $choices[$tier->name] = $tier->getId();
            }

            $builder->add($name, ChoiceType::class, [
                'choices' => $choices,
                'mapped' => false,
                'placeholder' => 'None',
                'required' => false,
            ]);
        } else {
            $builder->add($name, CheckboxType::class, [
                'mapped' => false,
                'required' => false,
            ]);
        }

        return ['name' => $name, 'kind' => $kind, 'entityId' => $id, 'tiered' => $tiered];
    }

    /**
     * @param array<Achievement> $achievements
     */
    private function populateCells(FormEvent $event, array $achievements): void
    {
        $student = $event->getData();
        if (!$student instanceof CourseClassStudent) {
            return;
        }

        $form = $event->getForm();
        $qualifications = $student->getQualifications();
        $awards = $student->getAwards();

        foreach ($achievements as $achievement) {
            $map = $achievement['kind'] === 'qualification' ? $qualifications : $awards;
            if (!array_key_exists($achievement['entityId'], $map)) {
                continue;
            }

            $tierId = $map[$achievement['entityId']];
            $form->get($achievement['name'])->setData($achievement['tiered'] ? $tierId : true);
        }
    }

    /**
     * @param array<Achievement> $achievements
     */
    private function applyCells(FormEvent $event, array $achievements): void
    {
        $student = $event->getData();
        if (!$student instanceof CourseClassStudent) {
            return;
        }

        $form = $event->getForm();
        $qualifications = [];
        $awards = [];

        foreach ($achievements as $achievement) {
            $value = $form->get($achievement['name'])->getData();
            if ($achievement['tiered']) {
                if ($value === null || $value === '') {
                    continue;
                }
                $tierId = (int)$value;
            } else {
                if ($value !== true) {
                    continue;
                }
                $tierId = null;
            }

            if ($achievement['kind'] === 'qualification') {
                $qualifications[$achievement['entityId']] = $tierId;
            } else {
                $awards[$achievement['entityId']] = $tierId;
            }
        }

        $student->setQualifications($qualifications);
        $student->setAwards($awards);
    }
}
