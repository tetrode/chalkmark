<?php

declare(strict_types=1);

namespace Chalkmark\Strategy;

use Chalkmark\RendererContext;
use Chalkmark\RenderState;

/**
 * Render any line while inside a fenced code block.
 */
final class CodeLineStrategy implements LineStrategy
{
    public function handle(
        string $content,
        string $prefix,
        RendererContext $renderer,
        RenderState $state,
        array &$out
    ): bool {
        if (!$state->inCode()) {
            return false;
        }
        $out[] = $prefix.$renderer->colorize('code', $content);

        return true;
    }
}
