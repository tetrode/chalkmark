<?php

use Chalkmark\Chalkmark;

require_once __DIR__ . '/../../vendor/autoload.php';

$argv = $_SERVER['argv'];
array_shift($argv);
$flag = array_shift($argv);
if ($flag === '--readme') {
    $cm = new Chalkmark(true, 'default');
    $cm->displayFile(__DIR__.'/README.md');
    exit(0);
} else {
    echo "Usage: php --readme";
}
