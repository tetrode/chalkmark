<?php

declare(strict_types=1);

namespace Chalkmark\Strategy;

use Chalkmark\RendererContext;
use Chalkmark\RenderState;

/**
 * Collapse multiple consecutive blank lines.
 */
final class BlankLineStrategy implements LineStrategy
{
    public function handle(
        string $content,
        string $prefix,
        RendererContext $renderer,
        RenderState $state,
        array &$out
    ): bool {
        if (trim($content) !== '') {
            return false;
        }
        if (empty($out) || end($out) !== '') {
            $out[] = '';
        }

        return true;
    }
}
