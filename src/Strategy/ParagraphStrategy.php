<?php

declare(strict_types=1);

namespace Chalkmark\Strategy;

use Chalkmark\RendererContext;
use Chalkmark\RenderState;

/**
 * Default paragraph rendering (plain text with inline formatting).
 */
final class ParagraphStrategy implements LineStrategy
{
    public function handle(
        string $content,
        string $prefix,
        RendererContext $renderer,
        RenderState $state,
        array &$out
    ): bool {
        // Paragraph strategy handles anything that remains (including whitespace-only lines are handled by BlankLineStrategy earlier)
        $out[] = $prefix.$renderer->colorize('text', $renderer->applyInlineAll($content));

        return true;
    }
}
