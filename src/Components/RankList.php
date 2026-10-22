<?php

declare(strict_types=1);

namespace Forumify\Milhq\Components;

use Doctrine\ORM\QueryBuilder;
use Forumify\Core\Component\List\AbstractDoctrineList;
use Forumify\Core\Security\VoterAttribute;
use Forumify\Milhq\Entity\Rank;
use Forumify\Milhq\Repository\RankGroupRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

/**
 * @extends AbstractDoctrineList<Rank>
 */
#[AsLiveComponent('RankList', '@ForumifyMilhqPlugin/frontend/components/rank_list.html.twig')]
class RankList extends AbstractDoctrineList
{
    #[LiveProp]
    public ?int $groupId = null;

    public function __construct(
        private readonly RankGroupRepository $rankGroupRepository,
        private readonly Security $security,
    ) {
    }

    protected function getEntityClass(): string
    {
        return Rank::class;
    }

    protected function getQuery(): QueryBuilder
    {
        $qb = parent::getQuery();

        $group = $this->groupId ? $this->rankGroupRepository->find($this->groupId) : null;
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
