<?php

declare(strict_types=1);

namespace Forumify\Milhq\Discord\Service;

class HtmlToDiscordTextConverter
{
    public function convert(?string $html): string
    {
        if ($html === null || trim($html) === '') {
            return '';
        }

        $text = $html;

        // Images aren't supported in embed descriptions, strip them entirely.
        $text = preg_replace('/<img[^>]*>/i', '', $text) ?? $text;

        // Headers: <h1> - <h5> -> #, ##, ###, ####, #####
        for ($level = 1; $level <= 5; $level++) {
            $hashes = str_repeat('#', $level);
            $text = preg_replace("/<h{$level}[^>]*>/i", "\n{$hashes} ", $text) ?? $text;
            $text = preg_replace("/<\/h{$level}>/i", "\n", $text) ?? $text;
        }

        // Bold
        $text = preg_replace('/<strong[^>]*>/i', '**', $text) ?? $text;
        $text = preg_replace('/<\/strong>/i', '**', $text) ?? $text;

        // Underline, note: <strong><u>text</u></strong> becomes ***text***
        $text = preg_replace('/<u[^>]*>/i', '*', $text) ?? $text;
        $text = preg_replace('/<\/u>/i', '*', $text) ?? $text;

        // Line breaks for common block level elements
        $text = preg_replace('/<br\s*\/?>/i', "\n", $text) ?? $text;
        $text = preg_replace('/<\/p>/i', "\n\n", $text) ?? $text;
        $text = preg_replace('/<p[^>]*>/i', '', $text) ?? $text;
        $text = preg_replace('/<\/li>/i', "\n", $text) ?? $text;
        $text = preg_replace('/<li[^>]*>/i', '- ', $text) ?? $text;
        $text = preg_replace('/<\/?(ul|ol|div)[^>]*>/i', "\n", $text) ?? $text;

        // Strip any remaining html tags
        $text = strip_tags($text);

        // Decode html entities (e.g. &amp;, &nbsp;)
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5);

        // Collapse excessive whitespace/newlines created by the conversion above
        $text = preg_replace('/[ \t]+/', ' ', $text) ?? $text;
        $text = preg_replace('/ *\n */', "\n", $text) ?? $text;
        $text = preg_replace('/\n{3,}/', "\n\n", $text) ?? $text;

        return trim($text);
    }
}
