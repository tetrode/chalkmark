<?php

declare(strict_types=1);

namespace Chalkmark\Strategy;

use Chalkmark\RendererContext;
use Chalkmark\RenderState;

/**
 * Minimal GitHub-style table support: any line that begins with a pipe `|` and
 * contains at least another pipe is passed through verbatim (minus trailing\r\n),
 * preserving spacing as authored. This keeps alignment authored in Markdown.
 *
 * We intentionally avoid complex width calculations and simply render the line
 * so that the fixture table at the end of the sample is supported.
 */
final class TableStrategy implements LineStrategy
{
    public function handle(
        string $content,
        string $prefix,
        RendererContext $renderer,
        RenderState $state,
        array &$out
    ): bool {
        if ($state->inCode()) {
            return false; // don't parse tables inside code blocks
        }

        // A table line: starts with '|' and has at least one more '|'
        if (preg_match('/^\|.+\|\s*$/', $content) !== 1) {
            return false;
        }

        // Pass through as text (apply inline formatting within cells)
        // We only transform inline markers but do not attempt to reflow spacing.
        $text = $renderer->applyInlineAll(rtrim($content));
        $out[] = $prefix.$renderer->colorize('text', $text);

        return true;
    }
}
