<?php
declare(strict_types=1);

namespace MeetMage\AdvancedSeo\Test\Unit\Model\Meta;

use MeetMage\AdvancedSeo\Model\Meta\TemplateRenderer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TemplateRendererTest extends TestCase
{
    private TemplateRenderer $renderer;

    protected function setUp(): void
    {
        $this->renderer = new TemplateRenderer();
    }

    /**
     * @param array<string, string> $variables
     */
    #[DataProvider('renderCases')]
    public function testRender(string $template, array $variables, string $expected): void
    {
        $this->assertSame($expected, $this->renderer->render($template, $variables));
    }

    /**
     * @return array<string, array{string, array<string, string>, string}>
     */
    public static function renderCases(): array
    {
        $vars = ['name' => 'Trail Shoe', 'store' => 'Shoe Shop', 'price' => '$59.00', 'attr:brand' => ''];

        return [
            'simple' => ['{{name}} | {{store}}', $vars, 'Trail Shoe | Shoe Shop'],
            'empty variable in the middle' => ['{{name}} | {{attr:brand}} | {{store}}', $vars, 'Trail Shoe | Shoe Shop'],
            'empty variable at the end' => ['{{name}} - {{attr:brand}}', $vars, 'Trail Shoe'],
            'empty variable at the start' => ['{{attr:brand}} · {{name}}', $vars, 'Trail Shoe'],
            'unknown variable' => ['{{name}} {{nope}}', $vars, 'Trail Shoe'],
            'spaces and case in braces' => ['{{ NAME }} for {{Price}}', $vars, 'Trail Shoe for $59.00'],
            'hyphen inside a word survives' => ['T-Shirt - {{attr:brand}}', $vars, 'T-Shirt'],
            'html and entities' => ['<p>Great</p><p>shoes</p> &amp; more', [], 'Great shoes & more'],
            'collapses whitespace' => ["Buy   {{name}}\n now", $vars, 'Buy Trail Shoe now'],
        ];
    }

    public function testRenderRespectsMaxLength(): void
    {
        $this->assertSame(
            'The quick brown',
            $this->renderer->render('The quick brown fox jumps over', [], 15)
        );
    }

    public function testTruncateKeepsShortText(): void
    {
        $this->assertSame('Short', $this->renderer->truncate('Short', 60));
    }

    public function testTruncateCutsLongWordHard(): void
    {
        $this->assertSame('Super', $this->renderer->truncate('Supercalifragilistic', 5));
    }

    public function testTruncateNeverEndsOnSeparator(): void
    {
        $this->assertSame('Red shoes', $this->renderer->truncate('Red shoes | Brand X', 11));
    }

    public function testTruncateIsMultibyteSafe(): void
    {
        $this->assertSame('Hyvä Theme', $this->renderer->truncate('Hyvä Theme Module', 12));
    }

    public function testGetAttributeCodes(): void
    {
        $this->assertSame(
            ['color', 'size'],
            $this->renderer->getAttributeCodes('{{attr:Color}} {{attr:size}} {{name}} {{ attr:color }}')
        );
    }
}
