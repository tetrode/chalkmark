<?php

// Reversed headers theme: like default, but headers use bright white foreground on colored backgrounds
// h1..h6 background colors mirror default foreground hues: red, green, yellow, blue, magenta, cyan
return [
    // Bright white (no bold) on colored backgrounds for headings
    'h1' => "\033[97;41m", // bright white on red bg
    'h2' => "\033[97;42m", // bright white on green bg
    'h3' => "\033[97;43m", // bright white on yellow bg
    'h4' => "\033[97;44m", // bright white on blue bg
    'h5' => "\033[97;45m", // bright white on magenta bg
    'h6' => "\033[97;46m", // bright white on cyan bg

    // Other styles follow the default theme
    'bullet' => "\033[35m", // magenta
    'code' => "\033[90m", // bright black (gray)
    'code_inline' => "\033[33m",
    'text' => "\033[0m", // default (no extra styling)
    'ordered' => "\033[36m",
    'hr' => "\033[2m",
    'italic' => "\033[3m",
    'bold' => "\033[1m",
    'bold_italic' => "\033[1;3m",
];
