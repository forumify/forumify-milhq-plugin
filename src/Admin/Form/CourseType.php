<?php

declare(strict_types=1);

namespace Forumify\Milhq\Admin\Form;

use Forumify\Core\Form\RichTextEditorType;
use Forumify\Milhq\Entity\Course;
use Forumify\Milhq\Entity\Rank;
use Forumify\Milhq\Repository\QualificationRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class CourseType extends AbstractType
{
    public function __construct(
        private readonly QualificationRepository $qualificationRepository,
        private readonly Packages $packages,
    ) {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Course::class,
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $imagePreview = empty($options['data']) ? null : $options['data']->getImage();

        $qualifications = $this->qualificationRepository
            ->createQueryBuilder('q')
            ->select('q.id', 'q.name', )
            ->getQuery()
            ->getArrayResult()
        ;
        $qualificationChoices = array_combine(
            array_column($qualifications, 'name'),
            array_column($qualifications, 'id'),
        );

        $builder
            ->add('title')
            ->add('description', RichTextEditorType::class)
            ->add('newImage', FileType::class, [
                'attr' => [
                    'preview' => $imagePreview
                        ? $this->packages->getUrl($imagePreview, 'forumify.asset')
                        : null,
                ],
                'constraints' => [
                    new Assert\Image(
                        maxSize: '10M',
                    ),
                ],
                'label' => 'Image',
                'mapped' => false,
                'required' => false,
            ])
            ->add('minimumRank', EntityType::class, [
                'autocomplete' => true,
                'choice_label' => 'name',
                'class' => Rank::class,
                'help' => 'What is the minimum rank required to take part in this course? (All ranks equal or above will be eligible)',
                'required' => false,
            ])
            ->add('prerequisites', ChoiceType::class, [
                'autocomplete' => true,
                'choices' => $qualificationChoices,
                'help' => 'Which qualifications are required to take part in this course?',
                'multiple' => true,
                'required' => false,
            ])
            ->add('qualifications', ChoiceType::class, [
                'autocomplete' => true,
                'choices' => $qualificationChoices,
                'help' => 'Which qualifications can be achieved by completing this course?',
                'multiple' => true,
                'required' => false,
            ])
        ;
    }
}
