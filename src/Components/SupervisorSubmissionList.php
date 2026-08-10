<?php

declare(strict_types=1);

namespace Forumify\Milhq\Components;

use Doctrine\ORM\QueryBuilder;
use Forumify\Core\Component\List\AbstractDoctrineList;
use Forumify\Milhq\Entity\FormSubmission;
use Forumify\Milhq\Repository\FormRepository;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

#[AsLiveComponent('Milhq\\SupervisorSubmissionList', '@ForumifyMilhqPlugin/frontend/components/supervisor_submission_list.html.twig')]
class SupervisorSubmissionList extends AbstractDoctrineList
{
    #[LiveProp]
    public int $soldierId;

    /** @var array<int> */
    #[LiveProp]
    public array $supervisedUnits;

    public function __construct(
        private readonly FormRepository $formRepository,
    ) {
    }

    protected function getEntityClass(): string
    {
        return FormSubmission::class;
    }

    protected function getQuery(): QueryBuilder
    {
        $qb = parent::getQuery();
        if (empty($this->supervisedUnits)) {
            return $qb->andWhere('1 = 0');
        }

        $forms = $this->formRepository
            ->addACLToQuery($this->formRepository->createQueryBuilder('e'), 'supervisor_manage_submissions')
            ->select('e.id')
            ->getQuery()
            ->getSingleColumnResult()
        ;

        $qb
            ->join('e.soldier', 's')
            ->join('s.unit', 'u')
            ->join('e.form', 'f')
            ->andWhere('s.id != :self')
            ->andWhere('u.id IN (:units)')
            ->andWhere('f.id IN (:forms)')
            ->orderBy('e.createdAt', 'DESC')
            ->setParameter('self', $this->soldierId)
            ->setParameter('units', $this->supervisedUnits)
            ->setParameter('forms', $forms)
        ;

        return $qb;
    }

    #[LiveListener('milhq:submission:status_updated')]
    public function refresh(): void
    {
        // no-op, re-rendering the component is enough to refresh the list with the current pagination
    }
}
