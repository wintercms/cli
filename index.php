#!/usr/bin/env php
<?php

if (version_compare(PHP_VERSION, '7.2.0', '<')) {
    echo 'This program requires PHP 7.2 or above to be installed.';
    exit(1);
}

// Load dependencies
require __DIR__.'/vendor/autoload.php';

use Winter\Cli\Application;

$app = new Application();
$app->run();
