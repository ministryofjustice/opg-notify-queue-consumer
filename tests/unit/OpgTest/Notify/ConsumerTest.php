<?php

declare(strict_types=1);

namespace OpgTest\Notify;

use Opg\Queue\Consumer;
use PHPUnit\Framework\TestCase;
use Psr\Log\Test\TestLogger;

class ConsumerTest extends TestCase
{
    private Consumer $consumer;
    private TestLogger $logger;

    public function setUp(): void
    {
        $this->logger = new TestLogger();
        $this->consumer = new Consumer($this->logger);
    }
    
    public function testRunReturnsStringSuccess(): void
    {
        $expectedResult = "Hello world";
        $this->consumer->run();

        self::assertTrue($this->logger->hasInfoThatContains("Running"), var_export($this->logger, true));
    }
}
