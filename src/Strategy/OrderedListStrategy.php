<?php

declare(strict_types=1);

namespace Chalkmark\Strategy;

use Chalkmark\RendererContext;
use Chalkmark\RenderState;

final class OrderedListStrategy implements LineStrategy
{
    public function handle(
        string $content,
        string $prefix,
        RendererContext $renderer,
        RenderState $state,
        array &$out
    ): bool {
        // This matches ordered list items like "1. text" or "1) text"
        /** @noinspection RegExpRedundantEscape */
        if (preg_match('/^(\s*)(\d{1,9})([\.)])\s+(.*)$/', $content, $m) !== 1) {
            return false;
        }
        $indent = $m[1];
        $num = $m[2];
        $sep = $m[3];
        $marker = $num.$sep;
        $text = rtrim($m[4]);
        $coloredMarker = $renderer->colorize('ordered', $marker);
        $text = $renderer->applyInlineAll($text);
        $out[] = $prefix.$indent.$coloredMarker.' '.$text;

        return true;
    }
}
