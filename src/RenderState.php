<?php

declare(strict_types=1);

namespace Chalkmark;

/**
 * Mutable render state shared across strategies.
 */
final class RenderState
{
    private bool $inCode = false;

    public function inCode(): bool
    {
        return $this->inCode;
    }

    public function toggleCode(): void
    {
        $this->inCode = !$this->inCode;
    }
}
