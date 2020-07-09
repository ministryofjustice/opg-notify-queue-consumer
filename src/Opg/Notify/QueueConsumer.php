<?php

declare(strict_types=1);

namespace Opg\Notify;

use Opg\Logging\Context;
use Psr\Log\LoggerInterface;

class QueueConsumer
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function run(): string
    {
        $this->logger->info('Running', ['context' => Context::NOTIFY_CONSUMER]);

        return "Hello world";
    }
}