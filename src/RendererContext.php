<?php

declare(strict_types=1);

namespace Chalkmark;

/**
 * Minimal renderer context exposed to strategies.
 */
interface RendererContext
{
    public function colorize(string $key, string $text): string;

    public function applyInlineAll(string $text): string;
}
