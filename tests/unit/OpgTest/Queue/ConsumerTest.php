<?php

declare(strict_types=1);

namespace OpgTest\Queue;

use Exception;
use Opg\Command\SendToNotify;
use Opg\Handler\SendToNotifyHandler;
use Opg\Queue\Consumer;
use Opg\Queue\QueueInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ConsumerTest extends TestCase
{
    private Consumer $consumer;
    private LoggerInterface $loggerMock;
    private SendToNotifyHandler $sendToNotifyHandlerMock;
    private QueueInterface $queueMock;

    public function setUp(): void
    {
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->sendToNotifyHandlerMock = $this->createMock(SendToNotifyHandler::class);
        $this->queueMock = $this->createMock(QueueInterface::class);
        $this->consumer = new Consumer($this->queueMock, $this->sendToNotifyHandlerMock, $this->loggerMock);
    }
    
    public function testMessageHandleSuccess(): void
    {
        $command = $this->createSendToNotifyCommand();

        $this->queueMock->expects(self::once())->method('next')->willReturn($command);
        $this->sendToNotifyHandlerMock->expects(self::once())->method('handle')->with($command);
        $this->queueMock->expects(self::once())->method('delete')->with($command);
        $this->loggerMock->expects(self::never())->method('critical');

        $this->consumer->run();
    }

    public function testFetchMessageFailure(): void
    {
        $this->queueMock->expects(self::once())->method('next')->willThrowException(new Exception('Uh oh...'));
        $this->sendToNotifyHandlerMock->expects(self::never())->method('handle');
        $this->queueMock->expects(self::never())->method('delete');
        $this->loggerMock
            ->expects(self::once())
            ->method('critical')
            ->with('Error processing message', self::anything());

        $this->consumer->run();
    }

    public function testHandleMessageFailure(): void
    {
        $command = $this->createSendToNotifyCommand();

        $this->queueMock->expects(self::once())->method('next')->willReturn($command);
        $this
            ->sendToNotifyHandlerMock
            ->expects(self::once())
            ->method('handle')
            ->with($command)
            ->willThrowException(new Exception('Uh oh...'))
        ;
        $this->queueMock->expects(self::never())->method('delete');
        $this->loggerMock
            ->expects(self::once())
            ->method('critical')
            ->with('Error processing message', self::anything());

        $this->consumer->run();
    }

    public function testDeleteMessageFailure(): void
    {
        $command = $this->createSendToNotifyCommand();

        $this->queueMock->expects(self::once())->method('next')->willReturn($command);
        $this->sendToNotifyHandlerMock->expects(self::once())->method('handle')->with($command);
        $this->queueMock
            ->expects(self::once())
            ->method('delete')
            ->with($command)
            ->willThrowException(new Exception('Uh oh...'));
        $this->loggerMock
            ->expects(self::once())
            ->method('critical')
            ->with('Error processing message', self::anything());

        $this->consumer->run();
    }

    private function createSendToNotifyCommand(): SendToNotify
    {
        return SendToNotify::fromArray(['id' => '1', 'uuid' => 'asd-123', 'filename' => 'this_is_a_test.pdf']);
    }
}
