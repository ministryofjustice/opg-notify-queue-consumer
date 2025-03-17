<?php

declare(strict_types=1);

use NotifyQueueConsumer\Logging\Context;
use NotifyQueueConsumer\Queue\Consumer;
use Psr\Log\LoggerInterface;

/** @var Consumer $consumer */
$doRunLoop = false;

require_once __DIR__ . '/../vendor/autoload.php';
$config = include __DIR__ . '/../src/bootstrap/config.php';
require_once __DIR__ . '/../src/bootstrap/logging.php';


// Initialise dependencies before starting the consumer
try {
    require_once __DIR__ . '/../src/bootstrap/services.php';

    /** @phpstan-ignore-next-line */
    while ($doRunLoop) {
        $consumer->run();

        sleep($config['consumer']['sleep_time']);
    }
} catch (Throwable $e) {
    exception_handler($e);
}

/** @var LoggerInterface $psrLoggerAdapter */
$psrLoggerAdapter->info('Finished', ['context' => Context::NOTIFY_CONSUMER]);

exit(0);
