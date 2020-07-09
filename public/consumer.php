<?php

declare(strict_types=1);

use Laminas\Log\Formatter\Json;
use Laminas\Log\Logger;
use Laminas\Log\PsrLoggerAdapter;
use Laminas\Log\Writer\Stream;
use Opg\Logging\Context;
use Opg\Notify\QueueConsumer;

require_once __DIR__ . '/../vendor/autoload.php';

// Setup dependencies
$formatter = new Json();
$logger = new Logger;
$writer = new Stream("php://stdout");

$writer->setFormatter($formatter);
$logger->addWriter($writer);

$psrLoggerAdapter = new PsrLoggerAdapter($logger);

// Initialise consumer and run it
$consumer = new QueueConsumer($psrLoggerAdapter);
$sleepTime = getenv('OPG_NOTIFY_QUEUE_CONSUMER_SLEEP_TIME') === false
    ? 1 : (int) getenv('OPG_NOTIFY_QUEUE_CONSUMER_SLEEP_TIME');
$run = true;

function shutdown_handler()
{
    global $psrLoggerAdapter, $run;
    $psrLoggerAdapter->info("Stopping", ['context' => Context::NOTIFY_CONSUMER]);
    $run = false;
}

pcntl_async_signals(true);
pcntl_signal(SIGINT, 'shutdown_handler');
pcntl_signal(SIGTERM, 'shutdown_handler');

while ($run) {
    $consumer->run();

    sleep($sleepTime);
}

$logger->info('Finished', ['context' => Context::NOTIFY_CONSUMER]);

exit(0);
