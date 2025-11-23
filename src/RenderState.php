<?php

declare(strict_types=1);

namespace Chalkmark;

/**
 * Mutable render state shared across strategies.
 */
final class RenderState
{
    private bool $inCode = false;

    // --- Table rendering state ---
    /** @var bool */
    public bool $collectingTable = false;
    /** @var string */
    public string $tablePrefix = '';
    /** @var list<list<string>> */
    public array $tableRows = [];
    /** @var list<'l'|'c'|'r'> */
    public array $tableAlign = [];

    public function resetTable(): void
    {
        $this->collectingTable = false;
        $this->tablePrefix = '';
        $this->tableRows = [];
        $this->tableAlign = [];
    }

    public function inCode(): bool
    {
        return $this->inCode;
    }

    public function toggleCode(): void
    {
        $this->inCode = !$this->inCode;
    }
}
