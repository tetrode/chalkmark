<?php


use Chalkmark\Chalkmark;

require __DIR__.'/../vendor/autoload.php';

$renderer = new Chalkmark([]);
$renderer->displayFile(__DIR__.'/fixtures/not_correct.md', STDOUT);

