<?php

declare(strict_types=1);

namespace NotifyQueueConsumer\Queue;

use UnexpectedValueException;

class DuplicateMessageException extends UnexpectedValueException
{
}
