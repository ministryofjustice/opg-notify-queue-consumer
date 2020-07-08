<?php

declare(strict_types=1);

namespace OpgTest\Notify;

use Opg\Notify\QueueConsumer;
use PHPUnit\Framework\TestCase;

class QueueConsumerTest extends TestCase
{
    private QueueConsumer $consumer;

    public function setUp(): void
    {
        $this->consumer = new QueueConsumer();
    }
    
    public function test_run_returns_string_success(): void
    {
        $expectedResult = "Hello world";
        $result = $this->consumer->run();

        self::assertEquals($expectedResult, $result);
    }
}