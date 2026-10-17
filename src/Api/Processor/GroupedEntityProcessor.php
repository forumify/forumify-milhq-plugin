<?php

declare(strict_types=1);

namespace Forumify\Milhq\Api\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Forumify\Core\Repository\AbstractRepository;
use Forumify\Milhq\Entity\GroupedEntityInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * @implements ProcessorInterface<GroupedEntityInterface, GroupedEntityInterface>
 */
class GroupedEntityProcessor implements ProcessorInterface
{
    /**
     * @param ProcessorInterface<GroupedEntityInterface, GroupedEntityInterface> $persistProcessor
     */
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private readonly ProcessorInterface $persistProcessor,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        $previous = $context['previous_data'] ?? null;
        if ($previous instanceof GroupedEntityInterface && $previous->getGroup()?->getId() !== $data->getGroup()?->getId()) {
            $this->moveToEndOfGroup($data);
        }

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }

    private function moveToEndOfGroup(GroupedEntityInterface $entity): void
    {
        $repository = $this->entityManager->getRepository($entity::class);
        if ($repository instanceof AbstractRepository) {
            $entity->setPosition($repository->getHighestPosition($entity) + 1);
        }
    }
}
