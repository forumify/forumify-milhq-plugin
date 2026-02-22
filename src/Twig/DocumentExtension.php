<?php

declare(strict_types=1);

namespace Forumify\Milhq\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class DocumentExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('milhq_document', [DocumentExtensionRuntime::class, 'parseDocument']),
        ];
    }
}
