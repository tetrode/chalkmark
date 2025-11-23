<?php

declare(strict_types=1);

namespace Chalkmark\Strategy;

use Chalkmark\RendererContext;
use Chalkmark\RenderState;

final class UnorderedListStrategy implements LineStrategy
{
    public function handle(
        string $content,
        string $prefix,
        RendererContext $renderer,
        RenderState $state,
        array &$out
    ): bool {
        // This matches unordered list items like "- text" or "* text"
        if (preg_match('/^(\s*)([-*+])\s+(.*)$/', $content, $m) !== 1) {
            return false;
        }
        $indent = $m[1];
        $marker = $m[2];
        $text = rtrim($m[3]);
        $coloredMarker = $renderer->colorize('bullet', $marker);
        $text = $renderer->applyInlineAll($text);
        $out[] = $prefix.$indent.$coloredMarker.' '.$text;

        return true;
    }
}
