<?php

// Banner theme: headers use bright white on strong background colors with full-width bars; others like default
return [
    // Bright white on colored backgrounds for headings (leverages HeadingStrategy background-fill)
    'h1' => "\033[97;41m", // red bg
    'h2' => "\033[97;42m", // green bg
    'h3' => "\033[97;43m", // yellow bg
    'h4' => "\033[97;44m", // blue bg
    'h5' => "\033[97;45m", // magenta bg
    'h6' => "\033[97;46m", // cyan bg

    // Other styles mirror default
    'bullet' => "\033[35m",
    'code' => "\033[90m",
    'code_inline' => "\033[33m",
    'text' => "\033[0m",
    'ordered' => "\033[36m",
    'hr' => "\033[2m",
    'italic' => "\033[3m",
    'bold' => "\033[1m",
    'bold_italic' => "\033[1;3m",
];
