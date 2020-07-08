<?php

declare(strict_types=1);

use Laminas\Log\Formatter\Json;
use Laminas\Log\Logger;
use Laminas\Log\Writer\Stream;
use Opg\Notify\QueueConsumer;

require_once __DIR__ . '/../vendor/autoload.php';

$formatter = new Json();
$logger = new Logger;
$writer = new Stream("php://stdout");

$writer->setFormatter($formatter);
$logger->addWriter($writer);

$consumer = new QueueConsumer($logger);

$consumer->run();

exit(0);
