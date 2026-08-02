<?php

declare(strict_types=1);

namespace Forumify\Milhq\Admin\Component;

use Doctrine\ORM\QueryBuilder;
use Forumify\Core\Repository\AbstractRepository;
use Forumify\Milhq\Entity\GroupedEntityInterface;
use Forumify\Milhq\Entity\GroupInterface;
use Forumify\Milhq\Repository\GroupScope;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\DefaultActionTrait;

abstract class AbstractGroupEditor
{
    use DefaultActionTrait;

    protected Security $security;

    #[Required]
    public function setSecurity(Security $security): void
    {
        $this->security = $security;
    }

    /**
     * @return AbstractRepository<GroupInterface&object>
     */
    abstract protected function getGroupRepository(): AbstractRepository;

    /**
     * @return AbstractRepository<GroupedEntityInterface&object>
     */
    abstract protected function getItemRepository(): AbstractRepository;

    /**
     * @return iterable<GroupedEntityInterface>
     */
    abstract public function getItems(GroupInterface $group): iterable;

    abstract public function getGroupRoutePrefix(): string;

    abstract public function getItemRoutePrefix(): string;

    abstract public function getGroupTranslationPrefix(): string;

    abstract public function getItemTranslationPrefix(): string;

    abstract public function getManagePermission(): string;

    /**
     * @return array<int, GroupInterface&object>
     */
    public function getGroups(): array
    {
        return $this->getGroupRepository()->findBy([], ['position' => 'ASC']);
    }

    /**
     * @return array<int, GroupedEntityInterface&object>
     */
    public function getUngroupedItems(): array
    {
        return $this->getItemRepository()->findBy(['group' => null], ['position' => 'ASC']);
    }

    #[LiveAction]
    public function reorderGroup(#[LiveArg] int $groupId, #[LiveArg] string $direction): void
    {
        if (!$this->security->isGranted($this->getManagePermission())) {
            return;
        }

        $group = $this->getGroupRepository()->find($groupId);
        if ($group === null) {
            return;
        }

        $this->getGroupRepository()->reorder($group, $direction);
    }

    #[LiveAction]
    public function reorderItem(#[LiveArg] int $itemId, #[LiveArg] string $direction): void
    {
        if (!$this->security->isGranted($this->getManagePermission())) {
            return;
        }

        $item = $this->getItemRepository()->find($itemId);
        if (!$item instanceof GroupedEntityInterface) {
            return;
        }

        $this->getItemRepository()->reorder(
            $item,
            $direction,
            static fn (QueryBuilder $qb) => GroupScope::apply($qb, $item->getGroup()),
        );
    }
}
