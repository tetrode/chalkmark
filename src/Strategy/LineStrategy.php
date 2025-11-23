<?php

declare(strict_types=1);

namespace Chalkmark\Strategy;

use Chalkmark\RendererContext;
use Chalkmark\RenderState;

/**
 * Strategy interface for handling a single Markdown line during rendering.
 */
interface LineStrategy
{
    /**
     * Attempt to handle the given content line.
     *
     * @param string $content Line content after removing any blockquote prefix
     * @param string $prefix Already-rendered blockquote prefix (may be empty)
     * @param RendererContext $renderer Renderer context for helpers (colors, inline formatting)
     * @param RenderState $state Mutable render state (e.g. in-code block flag)
     * @param array<int,string> $out Output buffer (append rendered lines)
     *
     * @return bool true if the strategy handled the line (no further strategies should run)
     */
    public function handle(
        string $content,
        string $prefix,
        RendererContext $renderer,
        RenderState $state,
        array &$out
    ): bool;
}
