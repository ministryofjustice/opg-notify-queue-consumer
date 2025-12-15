<?php

declare(strict_types=1);

namespace NotifyQueueConsumerTest\Unit\Logging;

use DateTimeImmutable;
use Monolog\Level;
use Monolog\LogRecord;
use NotifyQueueConsumer\Logging\OpgFormatter;
use PHPUnit\Framework\TestCase;

class OpgFormatterTest extends TestCase
{
    public function testFormatsLogRecord()
    {
        $date = new DateTimeImmutable('2020-01-03');

        $record = new LogRecord(
            $date,
            'my_service',
            Level::Info,
            'my message',
            ['x1' => 'field', 'trace_id' => 'my-trace-id'],
            ['x2' => 'field']
        );

        $sut = new OpgFormatter();

        $out = json_decode($sut->format($record), true);

        $this->assertEquals($date->format('c'), $out['time']);
        $this->assertEquals('INFO', $out['level']);
        $this->assertEquals('my message', $out['msg']);
        $this->assertEquals('my_service', $out['service_name']);
        $this->assertEquals('field', $out['context']['x1']);
        $this->assertEquals('field', $out['extra']['x2']);
        $this->assertEquals('my-trace-id', $out['trace_id']);
    }
}
