<?php

declare(strict_types=1);

namespace Chalkmark\Strategy;

use Chalkmark\RendererContext;
use Chalkmark\RenderState;

final class HeadingStrategy implements LineStrategy
{
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
        $out[] = $prefix.$renderer->colorize('h'.$level, $text);

        return true;
    }
}
