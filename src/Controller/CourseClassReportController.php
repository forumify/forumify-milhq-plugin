<?php

declare(strict_types=1);

namespace Forumify\Milhq\Controller;

use Forumify\Core\Security\VoterAttribute;
use Forumify\Milhq\Form\ClassResultType;
use Forumify\Milhq\Entity\CourseClass;
use Forumify\Milhq\Exception\SoldierNotFoundException;
use Forumify\Milhq\Repository\CourseClassRepository;
use Forumify\Milhq\Service\CourseClassService;
use Forumify\Plugin\Attribute\PluginVersion;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[PluginVersion('forumify/forumify-milhq-plugin', 'premium')]
#[Route('/courses/class', 'course_class_')]
class CourseClassReportController extends AbstractController
{
    public function __construct(
        private readonly CourseClassService $classService,
        private readonly CourseClassRepository $classRepository,
    ) {
    }

    #[Route('/{id}/report', 'report')]
    public function __invoke(CourseClass $class, Request $request): Response
    {
        $this->denyAccessUnlessGranted(VoterAttribute::ACL->value, [
            'entity' => $class->getCourse(),
            'permission' => 'manage_classes',
        ]);

        $form = $this->createForm(ClassResultType::class, $class);
        $form->handleRequest($request);
        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->render('@ForumifyMilhqPlugin/frontend/course/class_report.html.twig', [
                'class' => $class,
                'form' => $form->createView(),
            ]);
        }

        $alreadyProcessed = $class->getResult();

        $class->setResult(true);
        $this->classRepository->save($class);

        if (!$alreadyProcessed) {
            try {
                $this->classService->processResult($class);
            } catch (SoldierNotFoundException) {
                $this->addFlash('error', 'milhq.admin.requires_milhq_account');
            }
        }

        return $this->redirectToRoute('milhq_course_class_view', ['id' => $class->getId()]);
    }
}
