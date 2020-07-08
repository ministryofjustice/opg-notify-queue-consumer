<?php

declare(strict_types=1);

use Laminas\Log\Formatter\Json;
use Laminas\Log\Logger;
use Laminas\Log\PsrLoggerAdapter;
use Laminas\Log\Writer\Stream;
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
$consumer->run();

exit(0);
