<?php

use Chalkmark\Chalkmark;
use Chalkmark\Theme\ThemeRegistry;

require __DIR__.'/../vendor/autoload.php';

$sample = <<<MD
# Heading 1

## Heading 2

### Heading 3

Paragraph with *italic*, **bold**, and `inline code`.

- Bullet one
- Bullet two
MD;

foreach (ThemeRegistry::listBuiltins() as $name) {
    echo "\n==== Theme: {$name} ====\n";
    $renderer = new Chalkmark([], true, $name);
    echo $renderer->renderString($sample);
}
