<?php

declare(strict_types=1);

namespace Forumify\Milhq\Admin\Controller;

use Forumify\Admin\Crud\AbstractCrudController;
use Forumify\Milhq\Admin\Form\CourseInstructorType;
use Forumify\Milhq\Admin\Form\CourseType;
use Forumify\Milhq\Entity\Course;
use Forumify\Milhq\Entity\CourseInstructor;
use Forumify\Milhq\Repository\CourseInstructorRepository;
use Forumify\Plugin\Attribute\PluginVersion;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @extends AbstractCrudController<Course>
 */
#[PluginVersion('forumify/forumify-milhq-plugin', 'premium')]
#[Route('/courses', 'courses')]
class CourseController extends AbstractCrudController
{
    protected ?string $permissionView = 'forumify-milhq.admin.courses.view';
    protected ?string $permissionCreate = 'forumify-milhq.admin.courses.manage';
    protected ?string $permissionEdit = 'forumify-milhq.admin.courses.manage';
    protected ?string $permissionDelete = 'forumify-milhq.admin.courses.delete';

    protected string $formTemplate = '@ForumifyMilhqPlugin/admin/courses/form.html.twig';

    public function __construct(private readonly CourseInstructorRepository $instructorRepository)
    {
    }

    protected function getTranslationPrefix(): string
    {
        return 'milhq.' . parent::getTranslationPrefix();
    }

    protected function getEntityClass(): string
    {
        return Course::class;
    }

    protected function getTableName(): string
    {
        return 'Milhq\\CourseTable';
    }

    protected function getForm(?object $data): FormInterface
    {
        return $this->createForm(CourseType::class, $data);
    }

    #[Route('/{id}/new-instructor', '_create_instructor')]
    public function addInstructor(Request $request, Course $course): Response
    {
        $instructor = new CourseInstructor();
        $instructor->setCourse($course);

        return $this->handleInstructorForm($request, $instructor, true);
    }

    #[Route('/instructors/{id}', '_edit_instructor')]
    public function editInstructor(Request $request, CourseInstructor $instructor): Response
    {
        return $this->handleInstructorForm($request, $instructor, false);
    }

    private function handleInstructorForm(Request $request, CourseInstructor $instructor, bool $isNew): Response
    {
        $form = $this->createForm(CourseInstructorType::class, $instructor);
        $form->handleRequest($request);

        $course = $instructor->getCourse();

        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->render('@Forumify/form/simple_form_page.html.twig', [
                'admin' => true,
                'cancelPath' => $this->generateUrl('milhq_admin_courses_edit', [
                    'identifier' => $course->getId(),
                ]),
                'form' => $form->createView(),
                'title' => 'milhq.admin.course.instructor.' . ($isNew ? 'create' : 'edit'),
                'titleArgs' => $isNew ? [] : ['instructor' => $instructor->getTitle()],
            ]);
        }

        $instructor = $form->getData();
        $this->instructorRepository->save($instructor);
        $this->addFlash('success', 'milhq.admin.course.instructor.' . ($isNew ? 'created' : 'edited'));
        return $this->redirectToRoute('milhq_admin_courses_edit', ['identifier' => $course->getId()]);
    }

    #[Route('/instructors/{id}/delete', '_delete_instructor')]
    public function deleteInstructor(CourseInstructor $instructor): Response
    {
        $course = $instructor->getCourse();
        $this->instructorRepository->remove($instructor);

        $this->addFlash('success', 'milhq.admin.course.instructor.removed');
        return $this->redirectToRoute('milhq_admin_courses_edit', ['identifier' => $course->getId()]);
    }
}
