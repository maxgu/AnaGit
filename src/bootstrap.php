<?php

error_reporting(-1);

if (function_exists('ini_set')) {
    @ini_set('display_errors', 1);
    $memoryInBytes = function ($value) {
        $unit = strtolower(substr($value, -1, 1));
        $value = (int) $value;
        switch($unit) {
            case 'g':
                $value *= 1024;
            // no break (cumulative multiplier)
            case 'm':
                $value *= 1024;
            // no break (cumulative multiplier)
            case 'k':
                $value *= 1024;
        }
        return $value;
    };
    $memoryLimit = trim(ini_get('memory_limit'));
    // Increase memory_limit if it is lower than 1.5GB
    if ($memoryLimit != -1 && $memoryInBytes($memoryLimit) < 1024 * 1024 * 1536) {
        @ini_set('memory_limit', '1536M');
    }
    unset($memoryInBytes, $memoryLimit);
}

function includeIfExists($file)
{
    return file_exists($file) ? include $file : false;
}

if ((!$loader = includeIfExists(__DIR__.'/../vendor/autoload.php'))
    && (!$loader = includeIfExists(__DIR__.'/../../../autoload.php'))) {
    echo 'You must set up the project dependencies using `composer install`'.PHP_EOL;
    exit(1);
}

return $loader;