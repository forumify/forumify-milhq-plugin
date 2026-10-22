<?php

declare(strict_types=1);

namespace Forumify\Milhq\Components;

use Doctrine\ORM\QueryBuilder;
use Forumify\Core\Component\List\AbstractDoctrineList;
use Forumify\Core\Security\VoterAttribute;
use Forumify\Milhq\Entity\Award;
use Forumify\Milhq\Repository\AwardGroupRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

/**
 * @extends AbstractDoctrineList<Award>
 */
#[AsLiveComponent('AwardList', '@ForumifyMilhqPlugin/frontend/components/award_list.html.twig')]
class AwardList extends AbstractDoctrineList
{
    #[LiveProp]
    public ?int $groupId = null;

    public function __construct(
        private readonly AwardGroupRepository $awardGroupRepository,
        private readonly Security $security,
    ) {
    }

    protected function getEntityClass(): string
    {
        return Award::class;
    }

    protected function getQuery(): QueryBuilder
    {
        $qb = parent::getQuery();

        $group = $this->groupId ? $this->awardGroupRepository->find($this->groupId) : null;
        if ($group === null) {
            return $qb->andWhere('e.group IS NULL');
        }

        $canViewGroup = $this->security->isGranted(VoterAttribute::ACL->value, [
            'permission' => 'view',
            'entity' => $group,
        ]);
        if (!$canViewGroup) {
            throw new AccessDeniedHttpException();
        }

        return $qb->andWhere('e.group = :group')->setParameter('group', $group);
    }
}
