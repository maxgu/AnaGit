<?php

use Zend\ServiceManager\ServiceManager;
use Zelenin\Zend\Expressive\Config\ConfigManager;
use Zelenin\Zend\Expressive\Config\Provider\PhpProvider;

$productionMode = true; // environment variable

$providers =  [
    new PhpProvider(__DIR__ . '/autoload/*.global.php'),
    new PhpProvider(__DIR__ . '/autoload/*.local.php'),
];

$manager = new ConfigManager($providers);
$config = $manager->getConfig();

// Build container
$container = new ServiceManager($config['dependencies']);

// Inject config
$container->setService('config', $config);

return $container;