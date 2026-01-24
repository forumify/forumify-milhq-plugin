<?php

declare(strict_types=1);

namespace Forumify\Milhq\Components;

use DateTime;
use Doctrine\ORM\QueryBuilder;
use Forumify\Core\Component\List\AbstractDoctrineList;
use Forumify\Milhq\Entity\Course;
use Forumify\Milhq\Entity\CourseClass;
use Forumify\Milhq\Repository\CourseRepository;
use Forumify\Plugin\Attribute\PluginVersion;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

/**
 * @extends AbstractDoctrineList<CourseClass>
 */
#[PluginVersion('forumify/forumify-milhq-plugin', 'premium')]
#[AsLiveComponent('Milhq\\CourseClassList', '@ForumifyMilhqPlugin/frontend/components/course_class_list.html.twig')]
class CourseClassList extends AbstractDoctrineList
{
    #[LiveProp]
    public ?Course $course = null;

    #[LiveProp]
    public bool $signupOnly = false;

    public function __construct(private readonly CourseRepository $courseRepository)
    {
    }

    protected function getEntityClass(): string
    {
        return CourseClass::class;
    }

    protected function getQuery(): QueryBuilder
    {
        $qb = parent::getQuery()
            ->innerJoin('e.course', 'c')
            ->orderBy('e.start', 'DESC');

        if ($this->course !== null) {
            $qb
                ->andWhere('e.course = :course')
                ->setParameter('course', $this->course);
        }

        $this->courseRepository->addACLToQuery($qb, 'view', alias: 'c');

        if ($this->signupOnly) {
            $qb
                ->andWhere('e.start > :now')
                ->andWhere(':now BETWEEN e.signupFrom AND e.signupUntil')
                ->setParameter('now', new DateTime());
        }

        return $qb;
    }
}
