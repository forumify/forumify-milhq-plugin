<?php

declare(strict_types=1);

namespace Forumify\Milhq\Admin\Component;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Forumify\Core\Repository\AbstractRepository;
use Forumify\Milhq\Entity\GroupedEntityInterface;
use Forumify\Milhq\Entity\GroupInterface;
use Forumify\Milhq\Repository\GroupScopedRepositoryInterface;
use RuntimeException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * @template TGroup of GroupInterface
 * @template TItem of GroupedEntityInterface
 */
abstract class AbstractGroupEditor
{
    use DefaultActionTrait;

    public const UNGROUPED = 0;

    #[LiveProp(writable: true)]
    public string $search = '';

    /** @var list<int> */
    #[LiveProp]
    public array $openGroups = [];

    /** @var AbstractRepository<TGroup> */
    protected AbstractRepository $groupRepository;

    /** @var AbstractRepository<TItem>&GroupScopedRepositoryInterface */
    protected AbstractRepository $itemRepository;

    protected Security $security;

    /** @var array<int, int>|null */
    private ?array $matchCounts = null;

    /**
     * @return class-string<TGroup>
     */
    abstract protected function getGroupEntityClass(): string;

    /**
     * @return class-string<TItem>
     */
    abstract protected function getItemEntityClass(): string;

    abstract public function getGroupRoutePrefix(): string;

    abstract public function getItemRoutePrefix(): string;

    abstract public function getGroupTranslationPrefix(): string;

    abstract public function getItemTranslationPrefix(): string;

    abstract public function getManagePermission(): string;

    public function getSearchField(): string
    {
        return 'name';
    }

    #[Required]
    public function setServices(EntityManagerInterface $em, Security $security): void
    {
        $groupRepository = $em->getRepository($this->getGroupEntityClass());
        if (!$groupRepository instanceof AbstractRepository) {
            throw new RuntimeException('Your entity must have a repository that extends ' . AbstractRepository::class);
        }

        $itemRepository = $em->getRepository($this->getItemEntityClass());
        if (!$itemRepository instanceof AbstractRepository || !$itemRepository instanceof GroupScopedRepositoryInterface) {
            throw new RuntimeException('Your entity must have a repository that extends ' . AbstractRepository::class
                . ' and implements ' . GroupScopedRepositoryInterface::class);
        }

        /** @var AbstractRepository<TGroup> $groupRepository */
        $this->groupRepository = $groupRepository;
        /** @var AbstractRepository<TItem>&GroupScopedRepositoryInterface $itemRepository */
        $this->itemRepository = $itemRepository;
        $this->security = $security;
    }

    /**
     * Every group, followed by the section holding items without a group.
     *
     * @return list<array{group: TGroup|null, key: int, first: bool, last: bool}>
     */
    public function getSections(): array
    {
        $groups = $this->groupRepository->findBy([], ['position' => 'ASC']);

        $sections = [];
        foreach ($groups as $index => $group) {
            $sections[] = [
                'group' => $group,
                'key' => $group->getId(),
                'first' => $index === 0,
                'last' => $index === count($groups) - 1,
            ];
        }

        $sections[] = ['group' => null, 'key' => self::UNGROUPED, 'first' => false, 'last' => false];

        return $sections;
    }

    public function isOpen(?GroupInterface $group): bool
    {
        $key = $group?->getId() ?? self::UNGROUPED;

        return $this->search !== ''
            ? ($this->getMatchCounts()[$key] ?? 0) > 0
            : in_array($key, $this->openGroups, true);
    }

    /**
     * Only called for open groups, so collapsed groups cost no queries.
     *
     * @return list<TItem>
     */
    public function getItems(?GroupInterface $group): array
    {
        $qb = $this->itemRepository->createQueryBuilder('e')->orderBy('e.position', 'ASC');
        $this->itemRepository->applyGroupScope($qb, $group);
        $this->applySearch($qb);

        return $qb->getQuery()->getResult();
    }

    #[LiveAction]
    public function toggleGroup(#[LiveArg] int $groupId): void
    {
        $this->openGroups = in_array($groupId, $this->openGroups, true)
            ? array_values(array_diff($this->openGroups, [$groupId]))
            : [...$this->openGroups, $groupId];
    }

    #[LiveAction]
    public function reorderGroup(#[LiveArg] int $groupId, #[LiveArg] string $direction): void
    {
        if (!$this->security->isGranted($this->getManagePermission())) {
            return;
        }

        $group = $this->groupRepository->find($groupId);
        if ($group === null) {
            return;
        }

        $this->groupRepository->reorder($group, $direction);
    }

    #[LiveAction]
    public function reorderItem(#[LiveArg] int $itemId, #[LiveArg] string $direction): void
    {
        if (!$this->security->isGranted($this->getManagePermission())) {
            return;
        }

        $item = $this->itemRepository->find($itemId);
        if (!$item instanceof GroupedEntityInterface) {
            return;
        }

        $repository = $this->itemRepository;
        $repository->reorder(
            $item,
            $direction,
            static fn (QueryBuilder $qb) => $repository->applyGroupScope($qb, $item->getGroup()),
        );
    }

    private function applySearch(QueryBuilder $qb): void
    {
        if ($this->search === '') {
            return;
        }

        $qb
            ->andWhere('e.' . $this->getSearchField() . ' LIKE :search')
            ->setParameter('search', '%' . $this->search . '%');
    }

    /**
     * Number of matching items per group, so searching can open exactly the groups that contain a hit.
     *
     * @return array<int, int>
     */
    private function getMatchCounts(): array
    {
        if ($this->matchCounts !== null) {
            return $this->matchCounts;
        }

        $qb = $this->itemRepository
            ->createQueryBuilder('e')
            ->select('IDENTITY(e.group) AS groupId', 'COUNT(e) AS total')
            ->groupBy('e.group');
        $this->applySearch($qb);

        $counts = [];
        foreach ($qb->getQuery()->getScalarResult() as $row) {
            $counts[(int)($row['groupId'] ?? self::UNGROUPED)] = (int)$row['total'];
        }

        return $this->matchCounts = $counts;
    }
}
