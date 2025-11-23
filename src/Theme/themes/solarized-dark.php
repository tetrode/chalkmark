<?php

// Solarized Dark-inspired theme: balanced contrast; prefer 16-color SGR for portability; no backgrounds
return [
    // Headers (foreground only)
    'h1' => "\033[34m", // blue
    'h2' => "\033[36m", // cyan
    'h3' => "\033[32m", // green
    'h4' => "\033[33m", // yellow
    'h5' => "\033[36m", // cyan
    'h6' => "\033[34m", // blue

    // Accents and text
    'bullet' => "\033[36m",
    'ordered' => "\033[33m",
    'code' => "\033[90m",
    'code_inline' => "\033[33m",
    'text' => "\033[0m",
    'hr' => "\033[2m",
    'italic' => "\033[3m",
    'bold' => "\033[1m",
    'bold_italic' => "\033[1;3m",
];
