<?php

declare(strict_types=1);

namespace Opg\Queue;

use Exception;
use Opg\Command\Handler\UpdateDocumentStatusHandler;
use Throwable;
use Psr\Log\LoggerInterface;
use Opg\Command\Handler\SendToNotifyHandler;
use Opg\Logging\Context;

class Consumer
{
    private LoggerInterface $logger;
    private QueueInterface $queue;
    private SendToNotifyHandler $sendToNotifyHandler;
    private UpdateDocumentStatusHandler $updateDocumentStatusHandler;

    public function __construct(
        QueueInterface $queue,
        SendToNotifyHandler $sendToNotifyHandler,
        UpdateDocumentStatusHandler $updateDocumentStatusHandler,
        LoggerInterface $logger
    ) {
        $this->queue = $queue;
        $this->sendToNotifyHandler = $sendToNotifyHandler;
        $this->updateDocumentStatusHandler = $updateDocumentStatusHandler;
        $this->logger = $logger;
    }

    /**
     * @throws Exception
     */
    public function run(): void
    {
        $logExtras = ['context' => Context::NOTIFY_CONSUMER];
        $this->logger->info('Asking for next message', $logExtras);
        $sendToNotifyCommand = null;

        try {
            $sendToNotifyCommand = $this->queue->next();

            if (empty($sendToNotifyCommand)) {
                return;
            }

            $logExtras = array_merge(
                $logExtras,
                [
                    'id' => $sendToNotifyCommand->getId(),
                    'uuid' => $sendToNotifyCommand->getUuid()
                ]);
            $this->logger->info('Sending to Notify', $logExtras);
            $updateDocumentStatusCommand = $this->sendToNotifyHandler->handle($sendToNotifyCommand);

            $this->logger->info('Deleting processed message', $logExtras);
            $this->queue->delete($sendToNotifyCommand);

            $this->logger->info('Updating document status', $logExtras);
            $this->updateDocumentStatusHandler->handle($updateDocumentStatusCommand);
        } catch (DuplicateMessageException $e) {
            $this->logger->info('Deleting duplicate message', $logExtras);
            $this->queue->delete($sendToNotifyCommand);
        } catch (Throwable $e) {
            $logExtras = array_merge($logExtras, ['error' => (string)$e, 'trace' => $e->getTraceAsString()]);
            $this->logger->critical('Error processing message', $logExtras);
        }
    }
}
