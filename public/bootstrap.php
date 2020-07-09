<?php

declare(strict_types=1);

use Laminas\Log\Formatter\Json;
use Laminas\Log\Logger;
use Laminas\Log\PsrLoggerAdapter;
use Laminas\Log\Writer\Stream;
use Opg\Logging\Context;

require_once __DIR__ . '/../vendor/autoload.php';

$doRunLoop = true;

// Setup logging
$formatter = new Json();
$logger = new Logger;
$writer = new Stream("php://stdout");
$writer->setFormatter($formatter);
$logger->addWriter($writer);
$psrLoggerAdapter = new PsrLoggerAdapter($logger);

// Set custom handlers
function shutdown_handler()
{
    global $psrLoggerAdapter, $doRunLoop;

    $psrLoggerAdapter->info("Stopping", ['context' => Context::NOTIFY_CONSUMER]);
    $doRunLoop = false;
}

function exception_handler(Throwable $e)
{
    global $psrLoggerAdapter;

    $psrLoggerAdapter->critical(
        'Exception: ' . $e->getMessage(),
        [
            'context' => Context::NOTIFY_CONSUMER,
            'stacktrace' => $e->getTraceAsString(),
        ]
    );

    exit(1);
}

function error_handler($errno, $errstr, $errfile, $errline)
{
    global $psrLoggerAdapter;

    $extras = [
        'context' => Context::NOTIFY_CONSUMER,
        'errorno' => $errno,
        'errfile' => $errfile,
        'errline' => $errline,
    ];

    switch ($errno) {
        case E_NOTICE:
        case E_WARNING:
        case E_USER_WARNING:
        case E_USER_NOTICE:
            $psrLoggerAdapter->warning('Error: ' . $errstr, $extras);
            break;

        case E_USER_ERROR:
            $psrLoggerAdapter->critical('Fatal Error: ' . $errstr, $extras);
            exit(1);
            break;

        default:
            $psrLoggerAdapter->critical('Unknown Error: ' . $errstr, $extras);
            exit(1);
            break;
    }
}

pcntl_async_signals(true);
pcntl_signal(SIGINT, 'shutdown_handler');
pcntl_signal(SIGTERM, 'shutdown_handler');

set_error_handler('error_handler');
set_exception_handler('exception_handler');

