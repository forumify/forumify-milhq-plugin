<?php

declare(strict_types=1);

namespace Forumify\Milhq\Twig;

use Exception;
use Forumify\Core\Service\HTMLSanitizer;
use League\CommonMark\CommonMarkConverter;
use League\CommonMark\Exception\CommonMarkException;
use Twig\Extension\RuntimeExtensionInterface;

class TextExtensionRuntime implements RuntimeExtensionInterface
{
    public function __construct(private readonly HTMLSanitizer $sanitizer)
    {
    }

    public function convert(?string $input): string
    {
        if ($input === null) {
            return '';
        }

        $converter = new CommonMarkConverter([
            'allow_unsafe_links' => false,
            'max_nesting_level' => 50,
        ]);

        try {
            $content = $converter->convert($input)->getContent();
        } catch (Exception|CommonMarkException) {
            return 'An error occurred converting markdown to html.';
        }
        return '<div class="rich-text">' . $this->sanitizer->sanitize($content) . '</div>';
    }
}
