<?php

declare(strict_types=1);

namespace Forumify\Milhq\Components;

use DateTime;
use Forumify\Core\Component\List\AbstractDoctrineList;
use Forumify\Milhq\Entity\Course;
use Forumify\Milhq\Repository\CourseClassRepository;
use Forumify\Plugin\Attribute\PluginVersion;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

/**
 * @extends AbstractDoctrineList<Course>
 */
#[PluginVersion('forumify/forumify-milhq-plugin', 'premium')]
#[AsLiveComponent('Milhq\\CourseList', '@ForumifyMilhqPlugin/frontend/components/course_list.html.twig')]
class CourseList extends AbstractDoctrineList
{
    #[LiveProp]
    public bool $expanded = true;

    protected string|array|null $aclPermission = 'view';

    public function __construct(
        private readonly CourseClassRepository $courseClassRepository,
    ) {
    }

    protected function getEntityClass(): string
    {
        return Course::class;
    }

    public function lastClass(Course $course): ?DateTime
    {
        return $this->courseClassRepository
            ->findLastClassByCourse($course)
            ?->getStart();
    }
}
