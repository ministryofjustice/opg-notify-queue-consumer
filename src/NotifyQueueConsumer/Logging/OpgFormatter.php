<?php

declare(strict_types=1);

namespace NotifyQueueConsumer\Logging;

use Monolog\Formatter\NormalizerFormatter;
use Monolog\LogRecord;

class OpgFormatter extends NormalizerFormatter
{
    /**
     * {@inheritDoc}
     */
    public function format(LogRecord $record): string
    {
        $original = parent::format($record);

        $record = [
            'time' => $original['datetime'],
            'level' => $original['level_name'],
            'msg' => $original['message'],
            'service_name' => $original['channel'],
        ];

        if (isset($original['context']['trace_id'])) {
            $record['trace_id'] = $original['context']['trace_id'];
            unset($original['context']['trace_id']);
        }

        unset($original['datetime']);
        unset($original['level_name']);
        unset($original['message']);
        unset($original['channel']);

        return $this->toJson(array_filter($record + $original)) . "\n";
    }
}
