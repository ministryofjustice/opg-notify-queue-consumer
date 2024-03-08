<?php

declare(strict_types=1);

use Monolog\Logger;

require_once __DIR__ . '/../vendor/autoload.php';

// This bootstrap is shared between unit tests which don't have any env vars
// and functional tests which do and for which we want to setup services
if (getenv('OPG_NOTIFY_API_KEY') !== false) {
    $config = require_once __DIR__ . '/../src/bootstrap/config.php';
    $GLOBALS['config'] = $config;

    $psrLoggerAdapter = new Logger('test');
    $GLOBALS['psrLoggerAdapter'] = $psrLoggerAdapter;

    $GLOBALS['exportGlobalsInSuperGlobal'] = true;
    require_once __DIR__ . '/../src/bootstrap/services.php';
}
