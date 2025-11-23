<?php


use Chalkmark\Chalkmark;

require __DIR__.'/../vendor/autoload.php';

$renderer = new Chalkmark([],true, 'reversed');
$renderer->displayFile(__DIR__.'/../README.md', STDOUT);

