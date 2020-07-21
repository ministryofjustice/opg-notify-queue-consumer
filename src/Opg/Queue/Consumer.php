<?php

declare(strict_types=1);

namespace Opg\Queue;

use Throwable;
use Psr\Log\LoggerInterface;
use Opg\Handler\SendToNotifyHandler;
use Opg\Logging\Context;

class Consumer
{
    private LoggerInterface $logger;
    private QueueInterface $queue;
    private SendToNotifyHandler $handler;

    public function __construct(QueueInterface $queue, SendToNotifyHandler $handler, LoggerInterface $logger)
    {
        $this->queue = $queue;
        $this->handler = $handler;
        $this->logger = $logger;
    }

    public function run(): void
    {
        $logExtras = ['context' => Context::NOTIFY_CONSUMER];
        $this->logger->info('Fetching next item', $logExtras);

        try {
            $command = $this->queue->next();

            if ($command) {
                $logExtras = array_merge($logExtras, ['id' => $command->getId(), 'uuid' => $command->getUuid()]);
                $this->logger->info('Handling message', $logExtras);
                $this->handler->handle($command);

                $this->logger->info('Deleting message', $logExtras);
                $this->queue->delete($command);
            }
        } catch (Throwable $e) {
            $logExtras = array_merge($logExtras, ['error' => (string)$e, 'trace' => $e->getTraceAsString()]);
            $this->logger->critical('Error processing message', $logExtras);
        }
    }
}
