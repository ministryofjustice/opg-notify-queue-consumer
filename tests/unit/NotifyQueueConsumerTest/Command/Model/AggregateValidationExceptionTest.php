<?php

declare(strict_types=1);

namespace NotifyQueueConsumerTest\Unit\Command\Model;

use PHPUnit\Framework\TestCase;
use NotifyQueueConsumer\Command\Model\AggregateValidationException;

class AggregateValidationExceptionTest extends TestCase
{
    public function testGetInstanceReturnsSingletonSuccess(): void
    {
        $instanceOne = AggregateValidationException::getInstance();
        $instanceTwo = AggregateValidationException::getInstance();

        self::assertInstanceOf(AggregateValidationException::class, $instanceOne);
        self::assertSame($instanceOne, $instanceTwo);
    }

    public function testClearInstanceResetsExistingInstanceSuccess()
    {
        $instanceOne = AggregateValidationException::getInstance();

        AggregateValidationException::clearInstance();

        $instanceTwo = AggregateValidationException::getInstance();

        self::assertInstanceOf(AggregateValidationException::class, $instanceOne);
        self::assertInstanceOf(AggregateValidationException::class, $instanceTwo);
        self::assertNotSame($instanceOne, $instanceTwo);
    }

    public function testAddErrorAppendsMessageSuccess(): void
    {
        $messages = [];
        $messages[] = 'this is an error';
        $messages[] = 'something else went wrong';
        $messages[] = 'and this too';

        AggregateValidationException::clearInstance();

        foreach ($messages as $message) {
            AggregateValidationException::addError($message);
        }

        $exception = AggregateValidationException::getInstance();

        self::assertEquals(implode(', ', $messages), $exception->getMessage());
        self::assertTrue(AggregateValidationException::hasError());
    }

    public function testHasErrorReturnsFalseWithoutErrorsAddedSuccess(): void
    {
        AggregateValidationException::clearInstance();
        self::assertFalse(AggregateValidationException::hasError());
    }

    public function testCheckAndThrowDoesNothingWithoutErrorSuccess(): void
    {
        AggregateValidationException::clearInstance();
        AggregateValidationException::checkAndThrow();

        // If there's an exception we won't reach this point
        self::assertTrue(true);
    }

    public function testCheckAndThrowWithErrorThrowsExceptionSuccess(): void
    {
        AggregateValidationException::clearInstance();

        $message = 'this should throw';
        AggregateValidationException::addError($message);

        self::expectException(AggregateValidationException::class);
        self::expectExceptionMessage($message);

        AggregateValidationException::checkAndThrow();
    }
}
