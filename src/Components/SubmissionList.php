<?php

declare(strict_types=1);

namespace Forumify\Milhq\Components;

use Doctrine\ORM\QueryBuilder;
use Forumify\Core\Component\List\AbstractDoctrineList;
use Forumify\Milhq\Entity\FormSubmission;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

/**
 * @extends AbstractDoctrineList<FormSubmission>
 */
#[AsLiveComponent('SubmissionList', '@ForumifyMilhqPlugin/frontend/components/submission_list.html.twig')]
class SubmissionList extends AbstractDoctrineList
{
    #[LiveProp]
    public int $userId;

    protected function getEntityClass(): string
    {
        return FormSubmission::class;
    }

    protected function getQuery(): QueryBuilder
    {
        return parent::getQuery()
            ->where('e.user = :user')
            ->setParameter('user', $this->userId)
            ->orderBy('e.createdAt', 'DESC')
        ;
    }
}
