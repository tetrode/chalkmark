<?php

declare(strict_types=1);

namespace Chalkmark\Strategy;

use Chalkmark\RendererContext;
use Chalkmark\RenderState;

final class HeadingStrategy implements LineStrategy
{
    /**
     * Compute visible (non-ANSI) string length.
     */
    private function visibleLength(string $s): int
    {
        $noAnsi = (string)preg_replace('/\x1b\[[0-9;]*m/', '', $s);
        return function_exists('mb_strlen') ? mb_strlen($noAnsi) : strlen($noAnsi);
    }

    /**
     * Determine target line width: terminal width via COLUMNS env when available and >0; otherwise null.
     */
    private function terminalWidth(): ?int
    {
        $cols = getenv('COLUMNS');
        if ($cols === false || $cols === '') {
            return null;
        }
        if (is_numeric($cols)) {
            $n = (int)$cols;
            if ($n > 0) {
                return $n;
            }
        }

        return null;
    }

    /**
     * Whether the style sequence contains a background color (40–47 or 100–107).
     */
    private function hasBackground(string $style): bool
    {
        if ($style === '') {
            return false;
        }
        return preg_match('/\x1b\[[0-9;]*?(4[0-7]|10[0-7])m/', $style) === 1;
    }

    public function handle(
        string $content,
        string $prefix,
        RendererContext $renderer,
        RenderState $state,
        array &$out
    ): bool {
        // This matches headings like "# Heading" or "## Sub-heading"
        if (preg_match('/^(#{1,6})\s+(.*)$/', $content, $m) !== 1) {
            return false;
        }
        $level = strlen($m[1]);
        $text = rtrim($m[2]);
        $text = $renderer->applyInlineAll($text);

        $key = 'h'.$level;
        $styled = $text;

        // Only apply background-filling when colors are enabled AND the header style includes a background color.
        if ($renderer->colorsEnabled() && $this->hasBackground($renderer->getStyle($key))) {
            $prefixLen = $this->visibleLength($prefix);
            $visibleLen = $this->visibleLength($text);
            $term = $this->terminalWidth();
            $target = $term !== null ? $term : max(60, $visibleLen);
            $fillLen = max(0, $target - $prefixLen - $visibleLen);
            if ($fillLen > 0) {
                $styled = $text.str_repeat(' ', $fillLen);
            }
        }

        $out[] = $prefix.$renderer->colorize($key, $styled);

        return true;
    }
}
