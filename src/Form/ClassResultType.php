<?php

declare(strict_types=1);

namespace Forumify\Milhq\Form;

use Doctrine\Common\Collections\ArrayCollection;
use Forumify\Milhq\Entity\CourseClass;
use Forumify\Milhq\Twig\CourseExtensionRuntime;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ClassResultType extends AbstractType
{
    public function __construct(
        private readonly CourseExtensionRuntime $courseExtension,
        private readonly Packages $packages,
    ) {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CourseClass::class,
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('students', CollectionType::class, [
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'entry_options' => [
                    'course_class' => $options['data'],
                ],
                'entry_type' => ClassStudentResultType::class,
            ])
            ->add('instructors', CollectionType::class, [
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'entry_options' => [
                    'course_class' => $options['data'],
                ],
                'entry_type' => ClassInstructorResultType::class,
            ])
        ;
    }

    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $instructors = $view->children['instructors'];
        $this->setLabels($instructors->children);

        $students = $view->children['students'];
        $this->setLabels($students->children);
    }

    /**
     * @param array<FormView> $views
     */
    private function setLabels(array $views): void
    {
        $data = [];
        foreach ($views as $view) {
            $data[] = $view->vars['data'];
        }

        $soldiers = $this->courseExtension->getUsers(new ArrayCollection($data));

        foreach ($views as $view) {
            $id = $view->vars['data']->getSoldier()->getId();
            $soldier = $soldiers[$id]['soldier'] ?? null;
            if ($soldier === null) {
                continue;
            }

            $rankImg = $soldier->getRank()?->getImage();
            if ($rankImg !== null) {
                $rankImg = $this->packages->getUrl($rankImg, 'milhq.asset');
            }

            $view->vars['label_html'] = true;
            $view->vars['label'] = "<span class='flex items-center gap-1 mb-2'>
                <img width='24px' height='24px' src='{$rankImg}'>
                {$soldier->getName()}
            </span>";
        }
    }
}
