<?php

declare(strict_types=1);

namespace Chalkmark;

use Chalkmark\Strategy\BlankLineStrategy;
use Chalkmark\Strategy\CodeLineStrategy;
use Chalkmark\Strategy\FenceStrategy;
use Chalkmark\Strategy\HeadingStrategy;
use Chalkmark\Strategy\HorizontalRuleStrategy;
use Chalkmark\Strategy\LineStrategy;
use Chalkmark\Strategy\OrderedListStrategy;
use Chalkmark\Strategy\ParagraphStrategy;
use Chalkmark\Strategy\UnorderedListStrategy;
use Chalkmark\Strategy\TableStrategy;
use RuntimeException;

use const STDOUT;

/**
 * Simple Markdown-to-CLI renderer used by tests and the demo script.
 */
class Chalkmark implements RendererContext
{
    private bool $enableColors;

    /** @var array<string,string> Active color palette resolved from a theme */
    private array $palette = [];

    /**
     * @param bool $enableColors Whether to emit ANSI color sequences
     * @param string $theme Name of the theme to use (built-in or registered); also accepts a path to a PHP file returning an array
     * @param array<string,string|false|null> $colors Per-run overrides for theme colors
     */
    public function __construct(bool $enableColors = true, string $theme = 'default', array $colors = [])
    {
        $this->enableColors = $enableColors;
        // Load theme palette
        $this->palette = \Chalkmark\Theme\ThemeRegistry::get($theme);

        // Apply overrides on top of theme palette
        foreach ($colors as $key => $value) {
            if (!is_string($key)) {
                continue; // ignore non-string keys
            }
            if ($value === null || $value === false) {
                // Allow disabling a color by setting it to null/false
                $this->palette[$key] = '';
                continue;
            }
            // Cast everything else to string (e.g., custom ANSI sequence or empty string)
            $this->palette[$key] = (string)$value;
        }
    }

    /**
     * Render a Markdown string and return the CLI-friendly string. Always ends with a trailing newline.
     */
    public function renderString(string $markdown): string
    {
        // Normalize line endings to \n
        $normalized = str_replace(["\r\n", "\r"], "\n", $markdown);
        $lines = explode("\n", $normalized);
        // When reading from file with FILE_IGNORE_NEW_LINES, a trailing newline is dropped.
        // Emulate the same for strings by removing a single trailing empty element.
        if (!empty($lines) && end($lines) === '') {
            array_pop($lines);
        }

        return $this->renderLines($lines);
    }

    /**
     * Core rendering routine shared by renderFile() and renderString().
     *
     * @param list<string> $lines
     */
    private function renderLines(array $lines): string
    {
        $state = new RenderState();
        $strategies = $this->defaultStrategies();
        $out = [];

        foreach ($lines as $raw) {
            $line = rtrim((string)$raw, "\r\n");

            // Extract blockquote prefix and content
            [$prefix, $content] = $this->extractBlockquotePrefix($line);

            $handled = false;
            foreach ($strategies as $s) {
                if ($s->handle($content, $prefix, $this, $state, $out)) {
                    $handled = true;
                    break;
                }
            }
            if (!$handled) {
                // Safety: ParagraphStrategy should handle anything else
                $out[] = $prefix.$this->colorize('text', $this->applyInlineAll($content));
            }
        }

        // Flush any deferred structures (like tables) at EOF
        if ($state->collectingTable && $state->tableHandler instanceof TableStrategy) {
            $state->tableHandler->finalize($this, $state, $out);
        }

        // Ensure renderer ends with a blank line then newline, as tests expect
        if (empty($out) || end($out) !== '') {
            $out[] = '';
        }

        return implode("\n", $out)."\n";
    }

    /**
     * Default strategy pipeline in the correct order.
     *
     * @return array<int,LineStrategy>
     */
    private function defaultStrategies(): array
    {
        return [
            new FenceStrategy(),        // toggle code blocks, no output
            new CodeLineStrategy(),     // render code lines when in-code
            new HorizontalRuleStrategy(),
            new HeadingStrategy(),
            new OrderedListStrategy(),
            new UnorderedListStrategy(),
            new TableStrategy(),        // render simple table lines starting with '|'
            new BlankLineStrategy(),
            new ParagraphStrategy(),
        ];
    }

    /**
     * Extract the blockquote prefix (one or more pipes) and return [prefix, content].
     */
    private function extractBlockquotePrefix(string $line): array
    {
        if ($line === '') {
            return ['', ''];
        }
        // Match a blockquote prefix (one or more pipes) followed by whitespace
        if (preg_match('/^(\s*)((?:\|\s*)+)(.*)$/', $line, $m) !== 1) {
            // No blockquote; return line unchanged
            return ['', $line];
        }
        $indent = $m[1];
        $pipesChunk = $m[2];
        $rest = ltrim($m[3]);
        $levels = substr_count($pipesChunk, '|');
        $prefix = $indent.str_repeat('| ', $levels);

        return [$prefix, $rest];
    }

    // RendererContext implementation

    public function colorize(string $key, string $text): string
    {
        if (!$this->enableColors) {
            return $text;
        }
        $start = $this->palette[$key] ?? '';
        if ($start === '') {
            return $text;
        }
        $end = "\033[0m";

        return $start.$text.$end;
    }

    public function applyInlineAll(string $text): string
    {
        // Protect code spans first and replace with placeholders
        $placeholders = [];
        $i = 0;
        // This regex matches `code_inline` markers and replaces them with placeholders
        $text = (string)preg_replace_callback(
            '/(?<!\\\\)`([^`]+)`/',
            function (array $m) use (&$placeholders, &$i): string {
                $key = "\x00C{$i}\x00";
                $placeholders[$key] = $this->colorize('code_inline', $m[1]);
                $i++;

                return $key;
            },
            $text
        );

        // Image links: both syntaxes should be rendered as "Text: url" when URL looks like an image
        // Supported forms:
        //   ![Alt text](path/to/image.ext)
        //   [Alt text](path/to/image.ext)
        // We only transform if the URL ends with a common image extension.
        $imageExt = '(?i:\.(?:png|jpe?g|gif|webp|bmp|svg|tiff?|ico|avif))';
        /** @noinspection RegExpRedundantEscape */
        $text = (string)preg_replace_callback(
            '/(!?)\[([^\]]+)\]\(([^)\s]+)\)/',
            function (array $m) use ($imageExt): string {
                $url = $m[3];
                if (preg_match('/'.$imageExt.'(?:[#?].*)?$/', $url) !== 1) {
                    // Not an image URL; leave untouched
                    return $m[0];
                }
                $label = $m[2];

                return $label.': '.$url;
            },
            $text
        );

        // bold italic: ***text*** or ___text___
        $text = (string)preg_replace_callback('/(?<!\\\\)(\*\*\*|___)(.+?)(\1)/', function (array $m): string {
            return $this->colorize('bold_italic', $m[2]);
        }, $text);

        // bold: **text** or __text__
        $text = (string)preg_replace_callback('/(?<!\\\\)(\*\*|__)(.+?)(\1)/', function (array $m): string {
            return $this->colorize('bold', $m[2]);
        }, $text);

        // italic: *text* or _text_
        /** @noinspection RegExpSingleCharAlternation */
        $text = (string)preg_replace_callback('/(?<!\\\\)(\*|_)(.+?)(\1)/', function (array $m): string {
            return $this->colorize('italic', $m[2]);
        }, $text);

        // Unescape escaped markers \* -> * and \_ -> _
        $text = str_replace(['\\*', '\\_'], ['*', '_'], $text);

        // Restore code span placeholders
        if (!empty($placeholders)) {
            $text = strtr($text, $placeholders);
        }

        return $text;
    }

    // --- RendererContext extras ---

    public function colorsEnabled(): bool
    {
        return $this->enableColors;
    }

    public function getStyle(string $key): string
    {
        return $this->palette[$key] ?? '';
    }

    /**
     * Render and write to the provided stream (defaults to STDOUT).
     * @param resource|null $stream
     */
    public function displayFile(string $path, $stream = null): void
    {
        $buf = $this->renderFile($path);
        $stream = $stream ?? STDOUT;
        fwrite($stream, $buf);
    }

    /**
     * Render the file and return the CLI-friendly string. Always ends with a trailing newline.
     */
    public function renderFile(string $path): string
    {
        $lines = @file($path, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            throw new RuntimeException("Cannot read file: $path");
        }

        return $this->renderLines($lines);
    }
}
