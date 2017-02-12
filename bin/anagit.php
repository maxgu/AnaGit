#!/usr/bin/env php
<?php

if (PHP_SAPI !== 'cli') {
    echo 'Warning: AnaGit should be invoked via the CLI version of PHP, not the '.PHP_SAPI.' SAPI'.PHP_EOL;
}

require __DIR__.'/../src/bootstrap.php';

define('VERSION', '0.9.0');

use ZF\Console\Application;

$container = require __DIR__.'/../config/container.php';

$config = $container->get('config');
$app = new Application(
    'AnaGit',
    VERSION,
    $config['routes']
);
$exit = $app->run();
exit($exit);