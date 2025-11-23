<?php

declare(strict_types=1);

namespace Chalkmark\Strategy;

use Chalkmark\RendererContext;
use Chalkmark\RenderState;

/**
 * Toggle fenced code blocks on lines that are just ``` or ```lang.
 * Emits no output for the fence line itself.
 */
final class FenceStrategy implements LineStrategy
{
    public function handle(
        string $content,
        string $prefix,
        RendererContext $renderer,
        RenderState $state,
        array &$out
    ): bool {
        $t = trim($content);
        // This matches code blocks like "```" or "```php"
        /** @noinspection RegExpUnnecessaryNonCapturingGroup */
        /** @noinspection PhpExpressionWithoutClarifyingParenthesesInspection */
        /** @noinspection PhpArgumentWithoutNamedIdentifierInspection */
        if ($t !== '' && preg_match('/^```(?:(?:\w+))?\s*$/', $t) === 1) {
            $state->toggleCode();

            return true;
        }

        return false;
    }
}
