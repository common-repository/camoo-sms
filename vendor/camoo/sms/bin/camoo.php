#!/usr/bin/php -q
<?php

if (version_compare(PHP_VERSION, '8.1', '<')) {
    trigger_error('The CAMOO SMS Library requires PHP version 8.1 or higher', E_USER_ERROR);
}

try {
    if (file_exists(dirname(__DIR__) . '/vendor/autoload.php')) {
        // Downloaded installation
        require_once dirname(__DIR__) . '/vendor/autoload.php';
    } else {
        // Composer installation
        require_once dirname(__DIR__, 3) . '/autoload.php';
    }
} catch (Exception $err) {
    trigger_error($err->getMessage(), E_USER_ERROR);
}

use Camoo\Sms\Console\Runner;

$oRunner = new Runner();
$oRunner->run($argv);
exit(1);
