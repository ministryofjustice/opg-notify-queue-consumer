<?php

declare(strict_types=1);

namespace NotifyQueueConsumer\Queue;

use Exception;
use NotifyQueueConsumer\Command\Model\SendToNotify;

interface QueueInterface
{
    /**
     * @throws Exception
     * @return SendToNotify|null
     */
    public function next(): ?SendToNotify;

    /**
     * @throws Exception
     * @param SendToNotify $command
     */
    public function delete(SendToNotify $command): void;
}
