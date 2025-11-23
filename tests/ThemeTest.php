<?php

declare(strict_types=1);

namespace Chalkmark\Tests;

use Chalkmark\Chalkmark;
use Chalkmark\Theme\ThemeRegistry;
use PHPUnit\Framework\TestCase;

final class ThemeTest extends TestCase
{
    public function testMonochromeThemeProducesNoAnsi(): void
    {
        $renderer = new Chalkmark(true, 'monochrome');
        $out = $renderer->renderString("# Title\n\nParagraph");
        // No ANSI escape sequences should be present
        $this->assertSame(0, preg_match('/\x1b\[[0-9;]*m/', $out));
        $this->assertStringContainsString("Title\n\nParagraph\n\n", $out);
    }

    public function testOverridesMergeOnTopOfTheme(): void
    {
        // Start with monochrome (no colors), then override h1 to colored
        $renderer = new Chalkmark(true, 'monochrome', [
            'h1' => "\033[1;31m",
            'text' => '',
        ]);
        $out = $renderer->renderString("# Title\nText");
        // Expect at least one ANSI sequence because h1 is colored
        $this->assertSame(1, preg_match('/\x1b\[[0-9;]*m/', $out));
    }

    public function testReversedThemeBackgroundsForHeaders(): void
    {
        $renderer = new Chalkmark(true, 'reversed');
        $out = $renderer->renderString("# H1\n\n## H2\n\n### H3\n\nParagraph");
        // Look for background color codes 41, 42, 43 at least once
        $this->assertSame(1, preg_match('/\x1b\[[^m]*;41m/', $out));
        $this->assertSame(1, preg_match('/\x1b\[[^m]*;42m/', $out));
        $this->assertSame(1, preg_match('/\x1b\[[^m]*;43m/', $out));
    }

    public function testReversedThemeDoesNotAffectTextColoring(): void
    {
        // Ensure normal text still uses 'text' color (which resets to default, not background)
        $renderer = new Chalkmark(true, 'reversed');
        $out = $renderer->renderString("Paragraph only");
        // Should not include any background color codes 41-46 for plain text
        $this->assertSame(0, preg_match('/\x1b\[[0-9;]*4[1-6]m/', $out));
    }

    public function testHeaderBackgroundFillsTerminalWidth(): void
    {
        // Force terminal width via COLUMNS
        $prev = getenv('COLUMNS');
        putenv('COLUMNS=60');
        try {
            $renderer = new Chalkmark(true, 'reversed');
            $out = $renderer->renderString("# Title");
            // Take first rendered line
            $line = strtok($out, "\n");
            $this->assertIsString($line);
            // Strip ANSI
            $plain = (string)preg_replace('/\x1b\[[0-9;]*m/', '', (string)$line);
            $this->assertSame(60, strlen($plain), 'Header line should be padded to terminal width');
            // Ensure background code 41 (red bg for h1) is present
            $this->assertSame(1, preg_match('/\x1b\[[^m]*;41m/', (string)$line));
        } finally {
            // Restore env
            if ($prev === false) {
                putenv('COLUMNS');
            } else {
                putenv('COLUMNS='.$prev);
            }
        }
    }

    public function testHeaderBackgroundFallbackWidthWhenUnknownTerminal(): void
    {
        // Unset COLUMNS
        $prev = getenv('COLUMNS');
        if ($prev !== false) {
            putenv('COLUMNS');
        }

        try {
            $renderer = new Chalkmark(true, 'reversed');
            // Short header should be padded to 60
            $out = $renderer->renderString("# H1");
            $line = strtok($out, "\n");
            $plain = (string)preg_replace('/\x1b\[[0-9;]*m/', '', (string)$line);
            $this->assertSame(60, strlen($plain));

            // Long header (>60) should not be truncated nor padded below its own length
            $longText = str_repeat('X', 80);
            $out2 = $renderer->renderString('# '.$longText);
            $line2 = strtok($out2, "\n");
            $plain2 = (string)preg_replace('/\x1b\[[0-9;]*m/', '', (string)$line2);
            $this->assertSame(80, strlen($plain2));
        } finally {
            // restore
            if ($prev !== false) {
                putenv('COLUMNS='.$prev);
            }
        }
    }

    public function testBuiltinsContainNewThemes(): void
    {
        $builtins = ThemeRegistry::listBuiltins();
        $expected = ['nord','dracula','gruvbox-dark','solarized-dark','pastel-light','banner'];
        foreach ($expected as $name) {
            $this->assertContains($name, $builtins, "Built-in themes should include {$name}");
        }
    }

    public function testThemeRendersAnsiForHeaders(): void
    {
        $themes = ['nord','dracula','gruvbox-dark','solarized-dark','pastel-light','banner'];
        foreach ($themes as $t) {
            $renderer = new Chalkmark(true, $t);
            $out = $renderer->renderString("# Title");
            $this->assertSame(1, preg_match('/\x1b\[[0-9;]*m/', $out), "Theme {$t} should emit ANSI for headers");
        }
    }

    public function testNonBackgroundThemesHaveNoBackgrounds(): void
    {
        $themes = ['nord','dracula','gruvbox-dark','solarized-dark','pastel-light'];
        foreach ($themes as $t) {
            $renderer = new Chalkmark(true, $t);
            $out = $renderer->renderString("# Title\n\nParagraph");
            $line = strtok($out, "\n"); // first line (header)
            $this->assertIsString($line);
            $this->assertSame(0, preg_match('/\x1b\[[^m]*(4[1-6]|10[0-7])m/', (string)$line), "Theme {$t} should not use background colors for headers");
        }
    }

    public function testBannerThemeBackgroundsAndWidth(): void
    {
        $prev = getenv('COLUMNS');
        putenv('COLUMNS=60');
        try {
            $renderer = new Chalkmark(true, 'banner');
            $out = $renderer->renderString("# Title\n\nParagraph only");
            // Split lines robustly (strtok can be stateful and fragile when blanks exist)
            $lines = preg_split('/\n/', $out, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
            $this->assertIsArray($lines);
            $line = $lines[0] ?? null;
            $this->assertIsString($line);
            $plain = (string)preg_replace('/\x1b\[[0-9;]*m/', '', (string)$line);
            $this->assertSame(60, strlen($plain), 'Banner header should be padded to terminal width');
            // Should include a background code 41-46 or 100-107
            $this->assertSame(1, preg_match('/\x1b\[[^m]*(4[1-6]|10[0-7])m/', (string)$line));

            // Ensure normal text line (paragraph) has no background
            // Find the first non-empty, non-ANSI paragraph line after the header
            $paragraph = null;
            for ($i = 1; $i < count($lines); $i++) {
                $l = (string)$lines[$i];
                $plainL = (string)preg_replace('/\x1b\[[0-9;]*m/', '', $l);
                if (trim($plainL) !== '') {
                    $paragraph = $l;
                    break;
                }
            }
            $this->assertIsString($paragraph);
            $this->assertSame(0, preg_match('/\x1b\[[^m]*(4[1-6]|10[0-7])m/', (string)$paragraph));
        } finally {
            if ($prev === false) {
                putenv('COLUMNS');
            } else {
                putenv('COLUMNS='.$prev);
            }
        }
    }
}
