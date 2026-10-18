<?php

declare(strict_types=1);

namespace Forumify\Milhq\Form;

use Doctrine\Common\Collections\Collection;
use Forumify\Core\Form\RichTextEditorType;
use Forumify\Milhq\Entity\AfterActionReport;
use Forumify\Milhq\Entity\AfterActionReportAttendance;
use Forumify\Milhq\Entity\Soldier;
use Forumify\Milhq\Entity\Unit;
use Forumify\Milhq\Repository\SoldierRepository;
use JsonException;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<AfterActionReport>
 */
class AfterActionReportType extends AbstractType
{
    public function __construct(private readonly SoldierRepository $soldierRepository)
    {
    }

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
        $builder
            ->add('unit', EntityType::class, [
                'autocomplete' => true,
                'choice_label' => 'name',
                'class' => Unit::class,
                'disabled' => !$options['is_new'],
                'label' => 'Unit',
            ])
            ->add('attendanceJson', HiddenType::class, [
                'property_path' => 'attendance',
            ])
            ->add('report', RichTextEditorType::class);

        $builder->get('attendanceJson')->addModelTransformer(new CallbackTransformer(
            $this->attendanceToJson(...),
            $this->jsonToAttendance(...),
        ));
    }

    /**
     * @param Collection<int, AfterActionReportAttendance> $attendance
     */
    private function attendanceToJson(Collection $attendance): string
    {
        $soldierIdsByState = [];
        foreach ($attendance as $entry) {
            $soldierIdsByState[$entry->getState()][] = $entry->getSoldier()->getId();
        }

        return json_encode($soldierIdsByState, JSON_THROW_ON_ERROR);
    }

    /**
     * @return list<AfterActionReportAttendance>
     */
    private function jsonToAttendance(?string $json): array
    {
        if (empty($json)) {
            return [];
        }

        try {
            $soldierIdsByState = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $ex) {
            throw new TransformationFailedException($ex->getMessage(), previous: $ex);
        }

        if (!is_array($soldierIdsByState)) {
            throw new TransformationFailedException('Attendance must be a map of states to soldier ids.');
        }

        $stateBySoldierId = [];
        foreach ($soldierIdsByState as $state => $soldierIds) {
            foreach ((array)$soldierIds as $soldierId) {
                $stateBySoldierId[(int)$soldierId] = (string)$state;
            }
        }

        /** @var list<Soldier> $soldiers */
        $soldiers = $this->soldierRepository->findBy(['id' => array_keys($stateBySoldierId)]);

        return array_map(
            fn (Soldier $soldier) => new AfterActionReportAttendance($soldier, $stateBySoldierId[$soldier->getId()]),
            $soldiers,
        );
    }
}
