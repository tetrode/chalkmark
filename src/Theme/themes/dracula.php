<?php

// Dracula-inspired theme: vibrant magenta/green/yellow with cyan accents; no backgrounds
return [
    // Headers (foreground only)
    'h1' => "\033[38;5;95m",   // purple/magenta
    'h2' => "\033[38;5;118m",  // bright green
    'h3' => "\033[38;5;227m",  // bright yellow
    'h4' => "\033[38;5;87m",   // cyan
    'h5' => "\033[38;5;75m",   // blue
    'h6' => "\033[38;5;203m",  // red

    // Accents and text
    'bullet' => "\033[35m",
    'ordered' => "\033[36m",
    'code' => "\033[90m",
    'code_inline' => "\033[33m",
    'text' => "\033[0m",
    'hr' => "\033[2m",
    'italic' => "\033[3m",
    'bold' => "\033[1m",
    'bold_italic' => "\033[1;3m",
];
