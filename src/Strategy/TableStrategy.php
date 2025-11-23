<?php

declare(strict_types=1);

namespace Chalkmark\Strategy;

use Chalkmark\RendererContext;
use Chalkmark\RenderState;

/**
 * GitHub-style table support with alignment and column width normalization.
 *
 * Behavior:
 * - Detect contiguous lines starting with a pipe '|' as a table block.
 * - Parse the second line for alignment markers per column:
 *     :----  => left,  :----: => center,  ----: => right
 * - Compute column widths from all header/data rows (visible width).
 * - Render a normalized table:
 *     - Cells are padded according to alignment to match column width.
 *     - A separator row of dashes (no colons) matching each column width.
 * - Inline formatting is applied within cells.
 *
 * Notes:
 * - We collect the whole table and flush it when the first non-table line is
 *   encountered. This relies on a following non-table line (usually a blank
 *   line) existing after a table block, as is customary in Markdown.
 */
final class TableStrategy implements LineStrategy
{
    public function handle(
        string $content,
        string $prefix,
        RendererContext $renderer,
        RenderState $state,
        array &$out
    ): bool {
        // If we were collecting a table and the current line is not a table
        // line (or we're inside code), flush the collected table first.
        if ($state->collectingTable) {
            if ($state->inCode() || !$this->isTableLine($content, $prefix)) {
                $this->flushTable($renderer, $state, $out);
                // After flushing, do not handle this line here; let other strategies handle it.
                return false;
            }
        }

        if ($state->inCode()) {
            return false; // don't parse tables inside code blocks
        }

        // Start or continue table collection
        if ($this->isTableLine($content, $prefix)) {
            if (!$state->collectingTable) {
                $state->collectingTable = true;
                $state->tablePrefix = $prefix;
                $state->tableRows = [];
                $state->tableAlign = [];
                $state->tableHandler = $this;
            }
            $state->tableRows[] = $this->splitRow($content);

            return true; // handled by collecting
        }

        // Not a table line and not collecting one
        return false;
    }

    private function isTableLine(string $content, string $prefix): bool
    {
        // Our renderer treats a leading '|' as blockquote prefix "| ", so
        // table lines arrive here with prefix "| " (exactly one pipe level)
        // and content containing at least one internal pipe.
        if ($prefix !== '| ') {
            return false;
        }

        return str_contains($content, '|');
    }

    /**
     * Split a table line like "| a | b | c |" into an array of raw cell strings (trimmed).
     * Escaped pipes are not supported in this simple renderer.
     *
     * @return list<string>
     */
    private function splitRow(string $line): array
    {
        $trimmed = trim($line);
        // remove single leading and trailing pipes for consistent splitting
        if (str_starts_with($trimmed, '|')) {
            $trimmed = substr($trimmed, 1);
        }
        if (str_ends_with($trimmed, '|')) {
            $trimmed = substr($trimmed, 0, -1);
        }
        $parts = explode('|', $trimmed);
        $cells = [];
        foreach ($parts as $p) {
            $cells[] = trim($p);
        }

        return $cells;
    }

    /**
     * Determine if a row is an alignment row and return alignment for each column.
     * If not an alignment row, returns null.
     *
     * @param list<string> $cells
     * @return list<'l'|'c'|'r'>|null
     */
    private function parseAlignmentRow(array $cells): ?array
    {
        if ($cells === []) {
            return null;
        }
        $align = [];
        $any = false;
        foreach ($cells as $c) {
            $t = str_replace(' ', '', $c);
            if ($t === '') {
                // empty cell in alignment row -> treat as left
                $align[] = 'l';
                continue;
            }
            // must be all dashes optionally prefixed/suffixed with a colon
            if (preg_match('/^:?-{3,}:?$/', $t) !== 1) {
                return null; // not an alignment row
            }
            $any = true;
            $left = $t[0] === ':';
            $right = substr($t, -1) === ':';
            $align[] = $left && $right ? 'c' : ($right ? 'r' : 'l');
        }

        return $any ? $align : null;
    }

    /**
     * Flush the collected table to output, applying alignment and width normalization.
     *
     * @param array<int,string> $out
     */
    private function flushTable(RendererContext $renderer, RenderState $state, array &$out): void
    {
        $rows = $state->tableRows;
        $prefix = $state->tablePrefix;
        $state->resetTable();

        if (count($rows) === 0) {
            return;
        }

        // Detect alignment row at index 1 (second line), if present
        $align = [];
        if (isset($rows[1])) {
            $maybeAlign = $this->parseAlignmentRow($rows[1]);
            if ($maybeAlign !== null) {
                $align = $maybeAlign;
                // remove the alignment row from data
                array_splice($rows, 1, 1);
            }
        }

        // Determine number of columns
        $cols = 0;
        foreach ($rows as $r) {
            $cols = max($cols, count($r));
        }
        if ($cols === 0) {
            return; // nothing to render
        }

        // Default alignment to left for missing columns
        for ($i = 0; $i < $cols; $i++) {
            $align[$i] = $align[$i] ?? 'l';
        }

        // Compute widths per column (visible, without ANSI)
        $widths = array_fill(0, $cols, 0);
        $renderedCells = [];
        foreach ($rows as $ri => $r) {
            $renderedCells[$ri] = [];
            for ($ci = 0; $ci < $cols; $ci++) {
                $raw = $r[$ci] ?? '';
                $rendered = $renderer->applyInlineAll($raw);
                $renderedCells[$ri][$ci] = $rendered;
                $len = $this->visibleLength($rendered);
                if ($len > $widths[$ci]) {
                    $widths[$ci] = $len;
                }
            }
        }

        // Helper to pad a cell according to alignment
        $pad = function (string $text, int $w, string $a): string {
            $len = $this->visibleLength($text);
            $spaces = max(0, $w - $len);
            if ($a === 'r') {
                return str_repeat(' ', $spaces).$text;
            }
            if ($a === 'c') {
                $left = intdiv($spaces, 2);
                $right = $spaces - $left;
                return str_repeat(' ', $left).$text.str_repeat(' ', $right);
            }

            return $text.str_repeat(' ', $spaces); // left
        };

        // Build header row, separator, and data rows
        $lines = [];
        // All rows (including header at index 0)
        foreach ($renderedCells as $ri => $cells) {
            $formatted = [];
            for ($ci = 0; $ci < $cols; $ci++) {
                $cell = $cells[$ci] ?? '';
                $formatted[] = $pad($cell, $widths[$ci], $align[$ci]);
            }
            $line = $prefix.implode(' | ', $formatted).' |';
            $lines[] = $line;
            // After header (ri==0), insert the separator row
            if ($ri === 0) {
                $segs = [];
                for ($ci = 0; $ci < $cols; $ci++) {
                    $segs[] = str_repeat('-', $widths[$ci]);
                }
                $lines[] = $prefix.implode(' | ', $segs).' |';
            }
        }

        foreach ($lines as $l) {
            $out[] = $renderer->colorize('text', $l);
        }
    }

    private function visibleLength(string $s): int
    {
        // strip ANSI escape sequences
        $noAnsi = (string)preg_replace('/\x1b\[[0-9;]*m/', '', $s);
        // Treat as byte-length since input is ASCII for tests; mb_strlen safe too
        return function_exists('mb_strlen') ? mb_strlen($noAnsi) : strlen($noAnsi);
    }

    /**
     * Public hook for the renderer to finalize a possibly open table at EOF.
     */
    public function finalize(RendererContext $renderer, RenderState $state, array &$out): void
    {
        if ($state->collectingTable) {
            $this->flushTable($renderer, $state, $out);
        }
    }
}
