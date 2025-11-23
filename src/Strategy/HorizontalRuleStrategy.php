<?php

declare(strict_types=1);

namespace Chalkmark\Strategy;

use Chalkmark\RendererContext;
use Chalkmark\RenderState;

/**
 * Render horizontal rules denoted by a line of 3+ underscores.
 */
final class HorizontalRuleStrategy implements LineStrategy
{
    public function handle(
        string $content,
        string $prefix,
        RendererContext $renderer,
        RenderState $state,
        array &$out
    ): bool {
        $t = trim($content);
        // Matches a line of 3+ underscores and renders a fixed 40-underscore rule.
        if ($t === '' || preg_match('/^_{3,}$/', $t) !== 1) {
            return false;
        }
        // Insert a single blank line before; avoiding duplicates
        if (empty($out) || end($out) !== '') {
            $out[] = '';
        }
        $out[] = $prefix.$renderer->colorize('hr', str_repeat('_', 40));
        // Insert a single blank line after
        $out[] = '';

        return true;
    }
}
