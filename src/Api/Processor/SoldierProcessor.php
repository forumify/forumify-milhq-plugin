<?php

declare(strict_types=1);

namespace Forumify\Milhq\Api\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Forumify\Core\Twig\Extension\MenuRuntime;
use Forumify\Milhq\Entity\Soldier;
use Forumify\Milhq\Service\SyncSoldierService;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * @implements ProcessorInterface<Soldier, Soldier>
 */
class SoldierProcessor implements ProcessorInterface
{
    /**
     * @param ProcessorInterface<Soldier, Soldier> $persistProcessor
     */
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private readonly ProcessorInterface $persistProcessor,
        private readonly SyncSoldierService $syncSoldierService,
        private readonly CacheInterface $cache,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Soldier
    {
        $soldier = $this->persistProcessor->process($data, $operation, $uriVariables, $context);

        $user = $soldier->getUser();
        if ($user !== null) {
            $this->syncSoldierService->sync($user->getId());
            $this->cache->delete(MenuRuntime::createMenuCacheKey($user));
        }

        return $soldier;
    }
}
