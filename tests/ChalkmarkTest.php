<?php

declare(strict_types=1);

namespace Chalkmark\Tests;

use Chalkmark\Chalkmark;
use PHPUnit\Framework\TestCase;

use function dirname;

final class ChalkmarkTest extends TestCase
{
    public function testRenderFileProducesExpectedAnsiFreeOutput(): void
    {
        $renderer = new Chalkmark([], false); // colors disabled for stable assertions
        $path = $this->fixturePath('markdown_sample.md');
        $actual = $renderer->renderFile($path);

        $expected = "".
            "Heading 1\n".
            "\n".
            "Plain paragraph line with italic, bold, and bold italic, plus inline code.\n".
            "\n".
            "Heading 2\n".
            "\n".
            "- Bullet one\n".
            "- Bullet two\n".
            "  - Nested bullet\n".
            "* Star bullet with bold and italic and code\n".
            "+ Plus bullet with bold italic\n".
            "\n".
            "________________________________________\n".
            "\n".
            "Ordered Lists\n".
            "\n".
            "1. First item ls -la\n".
            "2. Second item\n".
            "   1) Sub one\n".
            "   2) Sub two\n".
            "3. Third item with paragraph:\n".
            "   This is a continuation paragraph under the third item.\n".
            "   | Blockquote under list item\n".
            "   | | Nested blockquote under list item\n".
            "\n".
            "Heading 3\n".
            "\n".
            "<?php echo 'hello';\n".
            "\n".
            "Heading 4\n".
            "Heading 5\n".
            "Heading 6\n".
            "\n".
            "Blockquotes\n".
            "\n".
            "| Simple blockquote with italic and bold text and cmd.\n".
            "| | Nested level 2 blockquote.\n".
            "| | Nested with space between pipes and bold italic.\n".
            "| Heading inside blockquote\n".
            "| - Bullet inside blockquote\n".
            "| <?php echo \"in blockquote\";\n".
            "\n";
        // renderer ends with a trailing newline

        $this->assertSame($expected, $actual);
    }

    private function fixturePath(string $name): string
    {
        return dirname(__DIR__).'/tests/fixtures/'.$name;
    }

    public function testDisplayFileWritesToStdout(): void
    {
        $renderer = new Chalkmark([], false);
        $tmp = $this->fixturePath('markdown_sample.md');
        // Write to a temp stream so we can assert reliably, then echo to CLI
        $stream = fopen('php://temp', 'w+');
        $this->assertIsResource($stream);
        $renderer->displayFile($tmp, $stream);
        rewind($stream);
        $buf = stream_get_contents($stream);
        fclose($stream);
        $this->assertIsString($buf);
        $this->assertMatchesRegularExpression('/^Heading 1/m', (string)$buf);
        // Re-emit to CLI to satisfy the requirement that the test writes to the CLI
        echo (string)$buf;
    }

    public function testImageLinksRenderToTextColonUrl(): void
    {
        $renderer = new Chalkmark([], false);
        $path = $this->fixturePath('images.md');
        $actual = $renderer->renderFile($path);

        $expected = "".
            "Chalkmark: images/chalkmark.png\n".
            "\n".
            "Logo: https://example.com/logo.svg\n".
            "An inline icon: icon: ./icon.jpg and a pic: pic: pic.jpeg in text.\n".
            "Not image [site](https://example.com/)\n".
            "Escaped code ![ignore](x.png) should not transform inside code\n".
            "map: map.png?size=2x\n".
            "\n";

        $this->assertSame($expected, $actual);
    }

    public function testRenderStringMatchesRenderFileForSample(): void
    {
        $renderer = new Chalkmark([], false);
        $path = $this->fixturePath('markdown_sample.md');
        $fromFile = $renderer->renderFile($path);
        $md = (string)file_get_contents($path);
        $fromString = $renderer->renderString($md);
        $this->assertSame($fromFile, $fromString);
    }

    public function testRenderStringHandlesTrailingNewlinePresence(): void
    {
        $renderer = new Chalkmark([], false);
        $mdNoNl = "# Title\n\nParagraph"; // no trailing newline
        $mdWithNl = $mdNoNl."\n"; // with trailing newline

        $a = $renderer->renderString($mdNoNl);
        $b = $renderer->renderString($mdWithNl);

        // Both should produce identical normalized output ending with a single newline (and extra blank line as per renderer contract)
        $this->assertSame($a, $b);
        $this->assertStringEndsWith("\n", $a);
    }
}
