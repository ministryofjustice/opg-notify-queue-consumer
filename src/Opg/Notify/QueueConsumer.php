<?php

declare(strict_types=1);

namespace Opg\Notify;

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
        $this->logger->info('Hello world');

        return "Hello world";
    }
}