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

    /**
     * Whether ANSI colors are enabled for this renderer instance.
     */
    public function colorsEnabled(): bool;

    /**
     * Return the raw ANSI style sequence configured for the given key, or '' when disabled.
     * This is used by strategies to make decisions (e.g., presence of background colors).
     */
    public function getStyle(string $key): string;
}
