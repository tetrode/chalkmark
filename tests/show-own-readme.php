<?php


use Chalkmark\Chalkmark;

require __DIR__.'/../vendor/autoload.php';

$renderer = new Chalkmark([]);
$renderer->displayFile(__DIR__.'/../README.md', STDOUT);

