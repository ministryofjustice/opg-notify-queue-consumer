<?php

declare(strict_types=1);

use Aws\Sqs\SqsClient;
use Opg\Handler\SendToNotifyHandler;
use Opg\Logging\Context;
use Opg\Queue\Consumer;
use Opg\Queue\SqsAdapter;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/bootstrap/logging.php';

// Initialise consumer and run it
$sleepTime = getenv('OPG_NOTIFY_QUEUE_CONSUMER_SLEEP_TIME') === false
    ? 1 : (int) getenv('OPG_NOTIFY_QUEUE_CONSUMER_SLEEP_TIME');

try {
    $awsSqsClient = new SqsClient();
    $awsQueueUrl = '';

    $queue = new SqsAdapter($awsSqsClient, $awsQueueUrl);
    $messageHandler = new SendToNotifyHandler();
    $consumer = new Consumer($queue, $messageHandler, $psrLoggerAdapter);

    while ($doRunLoop) {
        $consumer->run();

        sleep($sleepTime);
    }
} catch (Throwable $e) {
    exception_handler($e);
}

$psrLoggerAdapter->info('Finished', ['context' => Context::NOTIFY_CONSUMER]);

exit(0);
