<?php

declare(strict_types=1);

namespace Forumify\Milhq\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class TextExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('milhq_text', [TextExtensionRuntime::class, 'convert'], ['is_safe' => ['html']]),
        ];
    }
}
