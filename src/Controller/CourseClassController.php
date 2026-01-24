<?php

declare(strict_types=1);

namespace Forumify\Milhq\Controller;

use Forumify\Core\Security\VoterAttribute;
use Forumify\Milhq\Form\CourseClassType;
use Forumify\Milhq\Entity\Course;
use Forumify\Milhq\Entity\CourseClass;
use Forumify\Milhq\Repository\CourseClassRepository;
use Forumify\Plugin\Attribute\PluginVersion;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[PluginVersion('forumify/forumify-milhq-plugin', 'premium')]
#[Route('/courses', 'course_class_')]
class CourseClassController extends AbstractController
{
    public function __construct(
        private readonly CourseClassRepository $courseClassRepository,
    ) {
    }

    #[Route('/class/{id}', 'view')]
    public function view(CourseClass $class): Response
    {
        return $this->render('@ForumifyMilhqPlugin/frontend/course/class.html.twig', [
            'class' => $class,
        ]);
    }

    #[Route('/{slug:course}/class/create', 'create')]
    public function create(Request $request, Course $course): Response
    {
        $this->denyAccessUnlessGranted(VoterAttribute::ACL->value, [
            'entity' => $course,
            'permission' => 'manage_classes',
        ]);

        $class = new CourseClass();
        $class->setCourse($course);

        return $this->handleClassForm($request, true, $class);
    }

    #[Route('/class/{id}/edit', 'edit')]
    public function edit(Request $request, CourseClass $class): Response
    {
        $this->denyAccessUnlessGranted(VoterAttribute::ACL->value, [
            'entity' => $class->getCourse(),
            'permission' => 'manage_classes',
        ]);

        return $this->handleClassForm($request, false, $class);
    }

    #[Route('/class/{id}/delete', 'delete')]
    public function delete(Request $request, CourseClass $class): Response
    {
        if (!$request->query->get('confirmed')) {
            return $this->render('@ForumifyMilhqPlugin/frontend/course/class_delete.html.twig', [
                'class' => $class,
            ]);
        }

        $courseSlug = $class->getCourse()->getSlug();
        $this->courseClassRepository->remove($class);

        $this->addFlash('success', 'milhq.course.class.deleted');
        return $this->redirectToRoute('milhq_courses_view', ['slug' => $courseSlug]);
    }

    private function handleClassForm(Request $request, bool $isNew, CourseClass $class): Response
    {
        $form = $this->createForm(CourseClassType::class, $class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $class = $form->getData();
            $this->courseClassRepository->save($class);

            $this->addFlash('success', $isNew ? 'milhq.course.class.created' : 'milhq.course.class.edited');
            return $this->redirectToRoute('milhq_course_class_view', ['id' => $class->getId()]);
        }

        return $this->render('@Forumify/form/simple_form_page.html.twig', [
            'cancelPath' => $isNew
                ? $this->generateUrl('milhq_courses_view', ['slug' => $class->getCourse()->getSlug()])
                : $this->generateUrl('milhq_course_class_view', ['id' => $class->getId()]),
            'form' => $form->createView(),
            'title' => $isNew ? 'milhq.course.class.create' : 'milhq.course.class.edit',
        ]);
    }
}
