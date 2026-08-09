<?php

declare(strict_types=1);

namespace PluginTests\Tests\Unit\Discord\Service;

use Forumify\Milhq\Discord\Service\HtmlToDiscordTextConverter;
use PHPUnit\Framework\TestCase;

class HtmlToDiscordTextConverterTest extends TestCase
{
    private HtmlToDiscordTextConverter $converter;

    protected function setUp(): void
    {
        $this->converter = new HtmlToDiscordTextConverter();
    }

    public function testEmpty(): void
    {
        self::assertSame('', $this->converter->convert(null));
        self::assertSame('', $this->converter->convert(''));
        self::assertSame('', $this->converter->convert('   '));
    }

    public function testHeaders(): void
    {
        $html = '<h1>One</h1><h2>Two</h2><h3>Three</h3><h4>Four</h4><h5>Five</h5>';
        $expected = "# One\n\n## Two\n\n### Three\n\n#### Four\n\n##### Five";
        self::assertSame($expected, $this->converter->convert($html));
    }

    public function testBold(): void
    {
        self::assertSame('**bold text**', $this->converter->convert('<strong>bold text</strong>'));
    }

    public function testUnderline(): void
    {
        self::assertSame('*underlined text*', $this->converter->convert('<u>underlined text</u>'));
    }

    public function testBoldUnderlineCombined(): void
    {
        self::assertSame('***text***', $this->converter->convert('<strong><u>text</u></strong>'));
    }

    public function testImagesAreStripped(): void
    {
        self::assertSame('Hello world', $this->converter->convert('Hello <img src="foo.png" alt="bar"/> world'));
    }

    public function testRemainingTagsAreStripped(): void
    {
        self::assertSame('Hello world', $this->converter->convert('<span class="foo">Hello</span> <em>world</em>'));
    }

    public function testParagraphsAndBreaksProduceNewlines(): void
    {
        $html = '<p>First paragraph.</p><p>Second line.<br>Third line.</p>';
        self::assertSame("First paragraph.\n\nSecond line.\nThird line.", $this->converter->convert($html));
    }

    public function testEntitiesAreDecoded(): void
    {
        self::assertSame('Tom & Jerry', $this->converter->convert('Tom &amp; Jerry'));
    }
}
