<?php

declare(strict_types=1);

namespace OpgTest\Notify;

use Opg\Notify\QueueConsumer;
use PHPUnit\Framework\TestCase;
use Psr\Log\Test\TestLogger;

class QueueConsumerTest extends TestCase
{
    private QueueConsumer $consumer;
    private TestLogger $logger;

    public function setUp(): void
    {
        $this->logger = new TestLogger();
        $this->consumer = new QueueConsumer($this->logger);
    }
    
    public function testRunReturnsStringSuccess(): void
    {
        $expectedResult = "Hello world";
        $result = $this->consumer->run();

        self::assertEquals($expectedResult, $result);
        self::assertTrue($this->logger->hasInfoThatContains("Running"), var_export($this->logger, true));
    }
}
