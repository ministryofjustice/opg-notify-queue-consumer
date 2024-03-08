<?php

declare(strict_types=1);

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use NotifyQueueConsumer\Logging\Context;
use NotifyQueueConsumer\Logging\OpgFormatter;
use Psr\Log\LogLevel;

$doRunLoop = true;

// Setup logging
$streamHandler = new StreamHandler('php://stderr', LogLevel::INFO);
$streamHandler->setFormatter(new OpgFormatter());
$psrLoggerAdapter = new Logger('opg-notify-queue-consumer', [$streamHandler]);

// Set custom handlers
function shutdown_handler(): void
{
    global $psrLoggerAdapter, $doRunLoop;

    $psrLoggerAdapter->info("Stopping", ['context' => Context::NOTIFY_CONSUMER]);
    $doRunLoop = false;
}

function exception_handler(Throwable $e): void
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

/**
 * @param int          $errno
 * @param string       $errstr
 * @param string       $errfile
 * @param int          $errline
 * @return bool
 */
function error_handler($errno, $errstr, $errfile, $errline): bool
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

        default:
            $psrLoggerAdapter->critical('Unknown Error: ' . $errstr, $extras);
            exit(1);
    }

    return true;
}

pcntl_async_signals(true);
pcntl_signal(SIGINT, 'shutdown_handler');
pcntl_signal(SIGTERM, 'shutdown_handler');

set_error_handler('error_handler');
set_exception_handler('exception_handler');
