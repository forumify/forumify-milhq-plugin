<?php

declare(strict_types=1);

namespace Forumify\Milhq\MenuBuilder\MenuType;

use Forumify\Core\Entity\MenuItem;
use Forumify\Core\MenuBuilder\MenuType\AbstractMenuType;
use Forumify\Milhq\Service\EnlistService;
use Twig\Environment;

class MilhqMenuType extends AbstractMenuType
{
    public function __construct(
        private readonly Environment $twig,
        private readonly EnlistService $enlistService
    ) {
    }

    public function getType(): string
    {
        return 'milhq';
    }

    protected function render(MenuItem $item): string
    {
        return $this->twig->render('@ForumifyMilhqPlugin/frontend/menu/milhq.html.twig', [
            'canEnlist' => $this->enlistService->canEnlist(),
            'item' => $item,
        ]);
    }

    public function getPayloadFormType(): ?string
    {
        return MilhqMenuFormType::class;
    }
}
