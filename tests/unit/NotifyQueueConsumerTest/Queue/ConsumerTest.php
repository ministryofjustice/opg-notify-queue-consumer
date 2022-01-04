<?php

declare(strict_types=1);

namespace NotifyQueueConsumerTest\Unit\Queue;

use Exception;
use NotifyQueueConsumer\Command\Model\SendToNotify;
use NotifyQueueConsumer\Command\Model\UpdateDocumentStatus;
use NotifyQueueConsumer\Command\Handler\SendToNotifyHandler;
use NotifyQueueConsumer\Command\Handler\UpdateDocumentStatusHandler;
use NotifyQueueConsumer\Logging\Context;
use NotifyQueueConsumer\Queue\Consumer;
use NotifyQueueConsumer\Queue\DuplicateMessageException;
use NotifyQueueConsumer\Queue\QueueInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ConsumerTest extends TestCase
{
    private Consumer $consumer;
    private LoggerInterface $loggerMock;
    private SendToNotifyHandler $sendToNotifyHandlerMock;
    private QueueInterface $queueMock;
    private UpdateDocumentStatusHandler $updateDocumentStatusHandlerMock;

    public function setUp(): void
    {
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->sendToNotifyHandlerMock = $this->createMock(SendToNotifyHandler::class);
        $this->updateDocumentStatusHandlerMock = $this->createMock(UpdateDocumentStatusHandler::class);
        $this->queueMock = $this->createMock(QueueInterface::class);
        $this->consumer = new Consumer(
            $this->queueMock,
            $this->sendToNotifyHandlerMock,
            $this->updateDocumentStatusHandlerMock,
            $this->loggerMock
        );
    }

    /**
     * @throws Exception
     */
    public function testFetchMessagePostLetterSendToNotifyUpdateStatusSuccess(): void
    {
        $sendToNotifyCommand = $this->createSendToNotifyCommand();
        $updateDocumentStatusCommand = $this->createUpdateDocumentStatusCommand();

        $this->queueMock->expects(self::once())->method('next')->willReturn($sendToNotifyCommand);
        $this->sendToNotifyHandlerMock
            ->expects(self::once())
            ->method('handle')
            ->with($sendToNotifyCommand)
            ->willReturn($updateDocumentStatusCommand);
        $this->queueMock->expects(self::once())->method('delete')->with($sendToNotifyCommand);
        $this->updateDocumentStatusHandlerMock
            ->expects(self::once())
            ->method('handle')
            ->with($updateDocumentStatusCommand);
        $this->loggerMock->expects(self::never())->method('critical');

        $this->consumer->run();
    }

    /**
     * @throws Exception
     */
    public function testFetchMessageEmailInvoiceSendToNotifyUpdateStatusSuccess(): void
    {
        $sendToNotifyCommand = $this->createSendToNotifyCommand('email invoice');
        $updateDocumentStatusCommand = $this->createUpdateDocumentStatusCommand();

        $this->queueMock->expects(self::once())->method('next')->willReturn($sendToNotifyCommand);
        $this->sendToNotifyHandlerMock
            ->expects(self::once())
            ->method('handle')
            ->with($sendToNotifyCommand)
            ->willReturn($updateDocumentStatusCommand);
        $this->queueMock->expects(self::once())->method('delete')->with($sendToNotifyCommand);
        $this->updateDocumentStatusHandlerMock
            ->expects(self::once())
            ->method('handle')
            ->with($updateDocumentStatusCommand);
        $this->loggerMock->expects(self::never())->method('critical');

        $this->consumer->run();
    }

    /**
     * @throws Exception
     */
    public function testFetchMessageEmailLetterSendToNotifyUpdateStatusSuccess(): void
    {
        $sendToNotifyCommand = $this->createSendToNotifyCommand('email letter');
        $updateDocumentStatusCommand = $this->createUpdateDocumentStatusCommand();

        $this->queueMock->expects(self::once())->method('next')->willReturn($sendToNotifyCommand);
        $this->sendToNotifyHandlerMock
            ->expects(self::once())
            ->method('handle')
            ->with($sendToNotifyCommand)
            ->willReturn($updateDocumentStatusCommand);
        $this->queueMock->expects(self::once())->method('delete')->with($sendToNotifyCommand);
        $this->updateDocumentStatusHandlerMock
            ->expects(self::once())
            ->method('handle')
            ->with($updateDocumentStatusCommand);
        $this->loggerMock->expects(self::never())->method('critical');

        $this->consumer->run();
    }

    /**
     * @throws Exception
     */
    public function testNoMessageFetchedFailure(): void
    {
        $this->queueMock->expects(self::once())->method('next')->willReturn(null);
        $this->sendToNotifyHandlerMock->expects(self::never())->method('handle');
        $this->queueMock->expects(self::never())->method('delete');
        $this->updateDocumentStatusHandlerMock->expects(self::never())->method('handle');
        $this->loggerMock->expects(self::never())->method('critical');

        $this->consumer->run();
    }

    /**
     * @throws Exception
     */
    public function testFetchMessageFailure(): void
    {
        $this->queueMock->expects(self::once())->method('next')->willThrowException(new Exception('Uh oh...'));
        $this->sendToNotifyHandlerMock->expects(self::never())->method('handle');
        $this->queueMock->expects(self::never())->method('delete');
        $this->updateDocumentStatusHandlerMock->expects(self::never())->method('handle');
        $this->loggerMock
            ->expects(self::once())
            ->method('critical')
            ->with('Error processing message', self::anything());

        $this->consumer->run();
    }

    /**
     * @throws Exception
     */
    public function testHandleMessageFailure(): void
    {
        $command = $this->createSendToNotifyCommand();

        $this->queueMock->expects(self::once())->method('next')->willReturn($command);
        $this->sendToNotifyHandlerMock
            ->expects(self::once())
            ->method('handle')
            ->with($command)
            ->willThrowException(new Exception('Uh oh...'));
        $this->queueMock->expects(self::never())->method('delete');
        $this->updateDocumentStatusHandlerMock->expects(self::never())->method('handle');
        $this->loggerMock
            ->expects(self::once())
            ->method('critical')
            ->with('Error processing message', self::anything());

        $this->consumer->run();
    }

    /**
     * @throws Exception
     */
    public function testDuplicateMessageFailure(): void
    {
        $command = $this->createSendToNotifyCommand();

        $this->queueMock->expects(self::once())->method('next')->willReturn($command);
        $this->sendToNotifyHandlerMock
            ->expects(self::once())
            ->method('handle')
            ->with($command)
            ->willThrowException(new DuplicateMessageException());
        $this->queueMock->expects(self::once())->method('delete')->with($command);
        $this->updateDocumentStatusHandlerMock->expects(self::never())->method('handle');
        $this->loggerMock
            ->expects(self::atMost(3))
            ->method('info')
            ->withConsecutive(['Asking for next message'], ['Sending to Notify'], ['Deleting duplicate message'])
            ->willReturnOnConsecutiveCalls(['context' => Context::NOTIFY_CONSUMER]);

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
        $this->updateDocumentStatusHandlerMock->expects(self::never())->method('handle');
        $this->loggerMock
            ->expects(self::once())
            ->method('critical')
            ->with('Error processing message', self::anything());

        $this->consumer->run();
    }

    private function createSendToNotifyCommand(string $sendBy = null): SendToNotify
    {
        if($sendBy === 'email letter'){
            return SendToNotify::fromArray(
                [
                    'id' => '1',
                    'uuid' => 'asd-123',
                    'filename' => 'this_is_a_test.pdf',
                    'documentId' => '4545',
                    'recipientEmail' => 'test@test.com',
                    'recipientName' => 'Test Test',
                    'sendBy' => [
                        'method' => 'email',
                        'documentType' => 'letter'
                    ],
                    'letterType' => 'a6',
                ]
            );
        }
        if($sendBy === 'email invoice'){
            return SendToNotify::fromArray(
                [
                    'id' => '1',
                    'uuid' => 'asd-123',
                    'filename' => 'this_is_a_test.pdf',
                    'documentId' => '4545',
                    'recipientEmail' => 'test@test.com',
                    'recipientName' => 'Test Test',
                    'sendBy' => [
                        'method' => 'email',
                        'documentType' => 'invoice'
                    ],
                    'letterType' => 'a6',
                ]
            );
        }
        return SendToNotify::fromArray(
            [
                'id' => '1',
                'uuid' => 'asd-123',
                'filename' => 'this_is_a_test.pdf',
                'documentId' => '4545',
                'recipientEmail' => 'test@test.com',
                'recipientName' => 'Test Test',
                'sendBy' => [
                    'method' => 'post',
                    'documentType' => 'letter'
                ],
                'letterType' => 'a6',
            ]
        );
    }

    private function createUpdateDocumentStatusCommand(): UpdateDocumentStatus
    {
        return UpdateDocumentStatus::fromArray(
            [
                'notifyId' => '1',
                'notifyStatus' => 'accepted',
                'documentId' => '4545',
            ]
        );
    }
}
