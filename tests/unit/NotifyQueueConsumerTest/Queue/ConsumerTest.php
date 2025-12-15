<?php

declare(strict_types=1);

namespace NotifyQueueConsumerTest\Unit\Queue;

use Exception;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use NotifyQueueConsumer\Command\Handler\SendToNotifyHandler;
use NotifyQueueConsumer\Command\Handler\UpdateDocumentStatusHandler;
use NotifyQueueConsumer\Command\Model\SendToNotify;
use NotifyQueueConsumer\Command\Model\UpdateDocumentStatus;
use NotifyQueueConsumer\Logging\Context;
use NotifyQueueConsumer\Queue\Consumer;
use NotifyQueueConsumer\Queue\DuplicateMessageException;
use NotifyQueueConsumer\Queue\QueueInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use UnexpectedValueException;

class ConsumerTest extends TestCase
{
    private Consumer|MockObject $consumer;
    private LoggerInterface|MockObject $loggerMock;
    private SendToNotifyHandler|MockObject $sendToNotifyHandlerMock;
    private QueueInterface|MockObject $queueMock;
    private UpdateDocumentStatusHandler|MockObject $updateDocumentStatusHandlerMock;
    private bool $sleepMockCalled;

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
            $this->loggerMock,
            fn() => $this->sleepMockCalled = true,
        );
    }

    /**
     * @throws Exception
     */
    public function testFetchMessagePostLetterSendToNotifyUpdateStatusSuccess(): void
    {
        $command = $this->createSendToNotifyCommand();
        $updateDocumentStatusCommand = $this->createUpdateDocumentStatusCommand();

        $this->loggerMock->expects(self::exactly(5))->method('info')
            ->with(
                $this->callback(function ($value) {
                    return in_array($value, ['Asking for next message', 'Sending to Notify', 'Deleting processed message', 'Updating document status', 'Success']);
                }),
                $this->callback(function($context) {
                    return $context['context'] === Context::NOTIFY_CONSUMER && (!isset($context['trace_id']) || $context['trace_id'] === 'the-trace-id');
                })
            );

        $this->queueMock->expects(self::once())->method('next')->willReturn($command);
        $this->sendToNotifyHandlerMock
            ->expects(self::once())
            ->method('handle')
            ->with($command)
            ->willReturn($updateDocumentStatusCommand);
        $this->queueMock->expects(self::once())->method('delete')->with($command);
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
    public function testFetchMessagePostLetterSendToNotifyUpdateStatusFailsOnce(): void
    {
        $command = $this->createSendToNotifyCommand();
        $updateDocumentStatusCommand = $this->createUpdateDocumentStatusCommand();

        $this->queueMock->expects(self::once())->method('next')->willReturn($command);
        $this->sendToNotifyHandlerMock
            ->expects(self::once())
            ->method('handle')
            ->with($command)
            ->willReturn($updateDocumentStatusCommand);
        $this->queueMock->expects(self::once())->method('delete')->with($command);
        $this->updateDocumentStatusHandlerMock
            ->expects(self::exactly(2))
            ->method('handle')
            ->with($updateDocumentStatusCommand)
            ->willReturnOnConsecutiveCalls($this->throwException(new UnexpectedValueException('abc')), null);

        $this->loggerMock->expects(self::never())->method('critical');

        $this->consumer->run();

        $this->assertTrue($this->sleepMockCalled);
    }

    /**
     * @throws Exception
     */
    public function testFetchMessagePostLetterSendToNotifyUpdateStatusFailsWithClientException(): void
    {
        $command = $this->createSendToNotifyCommand();
        $updateDocumentStatusCommand = $this->createUpdateDocumentStatusCommand();

        $this->queueMock->expects(self::once())->method('next')->willReturn($command);
        $this->sendToNotifyHandlerMock
            ->expects(self::once())
            ->method('handle')
            ->with($command)
            ->willReturn($updateDocumentStatusCommand);
        $this->queueMock->expects(self::once())->method('delete')->with($command);
        $this->updateDocumentStatusHandlerMock
            ->expects(self::exactly(2))
            ->method('handle')
            ->with($updateDocumentStatusCommand)
            ->willReturnOnConsecutiveCalls(
                $this->throwException(new ClientException('some message', new Request('put', '/'), new Response(404, [], 'this is the problem'))),
                null
            );

        $this->loggerMock->expects(self::never())->method('critical');

        $this->consumer->run();

        $this->assertTrue($this->sleepMockCalled);
    }

    /**
     * @throws Exception
     */
    public function testFetchMessageEmailLetterSendToNotifyUpdateStatusSuccess(): void
    {
        $command = $this->createSendToNotifyCommand('email letter');
        $updateDocumentStatusCommand = $this->createUpdateDocumentStatusCommand();

        $this->queueMock->expects(self::once())->method('next')->willReturn($command);
        $this->sendToNotifyHandlerMock
            ->expects(self::once())
            ->method('handle')
            ->with($command)
            ->willReturn($updateDocumentStatusCommand);
        $this->queueMock->expects(self::once())->method('delete')->with($command);
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
            ->willReturnCallback(fn ($value) => match($value)
                {
                    'Asking for next message', 'Deleting duplicate message', 'Sending to Notify' => ['context' => Context::NOTIFY_CONSUMER],
                    default => throw new \LogicException()
                }
            );

        $this->consumer->run();
    }

    /**
     * @throws Exception
     */
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
                    'clientFirstName' => 'Devin',
                    'clientSurname' => 'Bahrke',
                    'sendBy' => [
                        'method' => 'email',
                        'documentType' => 'letter'
                    ],
                    'letterType' => 'a6',
                    'pendingOrDueReportType' => 'OPG104',
                    'caseNumber' => '74442574',
                    'replyToType' => 'HW'
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
                'clientFirstName' => 'Devin',
                    'clientSurname' => 'Bahrke',
                'sendBy' => [
                    'method' => 'post',
                    'documentType' => 'letter'
                ],
                'letterType' => 'a6',
                'pendingOrDueReportType' => null,
                'caseNumber' => '74442574',
                'replyToType' => 'HW',
                'trace_id' => 'the-trace-id',
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
                'sendByMethod' => 'email',
                'recipientEmailAddress' => 'test@test.com'
            ]
        );
    }
}
