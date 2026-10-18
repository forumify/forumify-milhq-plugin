<?php

declare(strict_types=1);

namespace Forumify\Milhq\Admin\Controller;

use Forumify\Core\Repository\AbstractRepository;
use Forumify\Milhq\Entity\GroupInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @phpstan-require-extends AbstractController
 */
trait GroupScopedCrudControllerTrait
{
    /**
     * @template TGroup of GroupInterface
     * @param AbstractRepository<TGroup> $groupRepository
     * @return TGroup|null
     */
    protected function getGroupFromRequest(AbstractRepository $groupRepository): ?GroupInterface
    {
        $groupId = $this->container
            ->get('request_stack')
            ->getCurrentRequest()
            ?->query
            ->getInt('group');

        if (!$groupId) {
            return null;
        }

        return $groupRepository->find($groupId);
    }
}
