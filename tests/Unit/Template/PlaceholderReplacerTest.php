<?php

declare(strict_types=1);

namespace Igeek\RectorHtmlOutput\Tests\Unit\Template;

use Igeek\RectorHtmlOutput\Template\PlaceholderReplacer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PlaceholderReplacerTest extends TestCase
{
    private PlaceholderReplacer $replacer;

    protected function setUp(): void
    {
        $this->replacer = new PlaceholderReplacer;
    }

    #[Test]
    public function replaceWithEmptyPlaceholders(): void
    {
        $template = 'Hello {{NAME}}';
        $result = $this->replacer->replace($template, []);

        $this->assertSame('Hello {{NAME}}', $result);
    }

    #[Test]
    public function replaceWithSinglePlaceholder(): void
    {
        $template = 'Hello {{NAME}}';
        $result = $this->replacer->replace($template, ['name' => 'World']);

        $this->assertSame('Hello World', $result);
    }

    #[Test]
    public function replaceWithMultiplePlaceholders(): void
    {
        $template = '{{GREETING}} {{NAME}}, you have {{COUNT}} messages';
        $result = $this->replacer->replace($template, [
            'greeting' => 'Hello',
            'name' => 'Alice',
            'count' => 5,
        ]);

        $this->assertSame('Hello Alice, you have 5 messages', $result);
    }

    #[Test]
    public function replaceConvertsKeysToUppercase(): void
    {
        $template = 'Value: {{VALUE}}';
        $result = $this->replacer->replace($template, ['value' => '123']);

        $this->assertSame('Value: 123', $result);
    }

    #[Test]
    public function replaceWithNumericValues(): void
    {
        $template = 'Count: {{COUNT}}';
        $result = $this->replacer->replace($template, ['count' => 42]);

        $this->assertSame('Count: 42', $result);
    }

    #[Test]
    public function replaceHandlesUnmatchedPlaceholders(): void
    {
        $template = '{{FOO}} and {{BAR}}';
        $result = $this->replacer->replace($template, ['foo' => 'Hello']);

        $this->assertSame('Hello and {{BAR}}', $result);
    }
}
