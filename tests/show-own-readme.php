<?php


use Chalkmark\Chalkmark;
use Chalkmark\Theme\ThemeRegistry;

require __DIR__.'/../vendor/autoload.php';

$argv = $_SERVER['argv'];
array_shift($argv);
$theme = array_shift($argv);
$builtins = ThemeRegistry::listBuiltins();
if (in_array($theme, $builtins)) {
    $renderer = new Chalkmark([], true, $theme);
    $renderer->displayFile(__DIR__.'/../README.md', STDOUT);
} else {
    echo PHP_EOL."No such theme: {$theme}";
    echo PHP_EOL."The following themes are available: ".implode(', ', $builtins);
    echo PHP_EOL;
}

