<?php

declare(strict_types=1);

use Opg\Logging\Context;
use Opg\Notify\QueueConsumer;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/bootstrap/logging.php';

// Initialise consumer and run it
$sleepTime = getenv('OPG_NOTIFY_QUEUE_CONSUMER_SLEEP_TIME') === false
    ? 1 : (int) getenv('OPG_NOTIFY_QUEUE_CONSUMER_SLEEP_TIME');

try {
    $consumer = new QueueConsumer($psrLoggerAdapter);

    while ($doRunLoop) {
        $consumer->run();

        sleep($sleepTime);
    }
} catch (Throwable $e) {
    exception_handler($e);
}

$psrLoggerAdapter->info('Finished', ['context' => Context::NOTIFY_CONSUMER]);

exit(0);
