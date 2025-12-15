<?php

declare(strict_types=1);

namespace NotifyQueueConsumerTest\Unit\Queue;

use Aws\Result;
use Aws\Sqs\SqsClient;
use Exception;
use NotifyQueueConsumer\Command\Model\SendToNotify;
use NotifyQueueConsumer\Queue\SqsAdapter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;

class SqsAdapterTest extends TestCase
{
    private const DEFAULT_WAIT_TIME = 0;
    private const QUEUE_URL = 'url...';

    private MockObject|SqsClient $sqsClientMock;

    public function setUp(): void
    {
        // The AWS SDK uses magic methods so we need a workaround to mock them
        $this->sqsClientMock = $this->createMock(SqsClient::class);
    }

    /**
     * @throws Exception
     */
    public function testNextReturnsMessageSuccessPostLetter(): void
    {
        $awsResult = $this->createMock(Result::class);
        $config = [
            'AttributeNames' => ['SentTimestamp'],
            'MaxNumberOfMessages' => 1,
            'MessageAttributeNames' => ['All'],
            'QueueUrl' => self::QUEUE_URL,
            'WaitTimeSeconds' => self::DEFAULT_WAIT_TIME,
        ];

        $rawBody = $this->getMessage(
            null,
            null,
            'post',
            'letter',
            null,
            null,
            null,
            null,
            null,
            null);

        $rawData = [
            'ReceiptHandle' => 'handle-12345',
            'Body' => json_encode($rawBody),
        ];
        $expectedResult = SendToNotify::fromArray(
            [
                'id' => $rawData['ReceiptHandle'],
                'uuid' => $rawBody['message']['uuid'],
                'filename' => $rawBody['message']['filename'],
                'documentId' => $rawBody['message']['documentId'],
                'recipientEmail' => $rawBody['message']['recipientEmail'],
                'recipientName' => $rawBody['message']['recipientName'],
                'clientFirstName' => $rawBody['message']['clientFirstName'],
                'clientSurname' => $rawBody['message']['clientSurname'],
                'sendBy' => $rawBody['message']['sendBy'],
                'letterType' => $rawBody['message']['letterType'],
                'pendingOrDueReportType' => $rawBody['message']['pendingOrDueReportType'],
                'caseNumber' => $rawBody['message']['caseNumber'],
                'replyToType' => $rawBody['message']['replyToType']
            ]
        );

        $awsResult->method('get')->with('Messages')->willReturn([$rawData]);

        $this->sqsClientMock->expects(self::once())->method('__call')->with('receiveMessage', [$config])->willReturn($awsResult);

        $sqsAdapter = new SqsAdapter($this->sqsClientMock, self::QUEUE_URL, self::DEFAULT_WAIT_TIME);

        $actualResult = $sqsAdapter->next();

        self::assertEquals($expectedResult->getId(), $actualResult->getId());
        self::assertEquals($expectedResult->getUuid(), $actualResult->getUuid());
        self::assertEquals($expectedResult->getFilename(), $actualResult->getFilename());
    }

    /**
     * @throws Exception
     */
    public function testNextReturnsMessageSuccessPostLPALetter(): void
    {
        $awsResult = $this->createMock(Result::class);
        $config = [
            'AttributeNames' => ['SentTimestamp'],
            'MaxNumberOfMessages' => 1,
            'MessageAttributeNames' => ['All'],
            'QueueUrl' => self::QUEUE_URL,
            'WaitTimeSeconds' => self::DEFAULT_WAIT_TIME,
        ];
        $rawBody = $this->getMessage(
            null,
            null,
            'post',
            'letter',
            null,
            null,
            null,
            null,
            null,
            null);

        $rawData = [
            'ReceiptHandle' => 'handle-12345',
            'Body' => json_encode($rawBody),
        ];
        $expectedResult = SendToNotify::fromArray(
            [
                'id' => $rawData['ReceiptHandle'],
                'uuid' => $rawBody['message']['uuid'],
                'filename' => $rawBody['message']['filename'],
                'documentId' => $rawBody['message']['documentId'],
                'recipientEmail' => $rawBody['message']['recipientEmail'],
                'recipientName' => $rawBody['message']['recipientName'],
                'clientFirstName' => $rawBody['message']['clientFirstName'],
                'clientSurname' => $rawBody['message']['clientSurname'],
                'sendBy' => $rawBody['message']['sendBy'],
                'letterType' => $rawBody['message']['letterType'],
                'pendingOrDueReportType' => $rawBody['message']['pendingOrDueReportType'],
                'caseNumber' => $rawBody['message']['caseNumber'],
                'replyToType' => $rawBody['message']['replyToType']
            ]
        );

        $awsResult->method('get')->with('Messages')->willReturn([$rawData]);

        $this->sqsClientMock->expects(self::once())->method('__call')->with('receiveMessage', [$config])->willReturn($awsResult);

        $sqsAdapter = new SqsAdapter($this->sqsClientMock, self::QUEUE_URL, self::DEFAULT_WAIT_TIME);

        $actualResult = $sqsAdapter->next();

        self::assertEquals($expectedResult->getId(), $actualResult->getId());
        self::assertEquals($expectedResult->getUuid(), $actualResult->getUuid());
        self::assertEquals($expectedResult->getFilename(), $actualResult->getFilename());
    }

    /**
     * @throws Exception
     */
    public function testNextReturnsMessageSuccessEmailLetter(): void
    {
        $awsResult = $this->createMock(Result::class);
        $config = [
            'AttributeNames' => ['SentTimestamp'],
            'MaxNumberOfMessages' => 1,
            'MessageAttributeNames' => ['All'],
            'QueueUrl' => self::QUEUE_URL,
            'WaitTimeSeconds' => self::DEFAULT_WAIT_TIME,
        ];

        $rawBody = $this->getMessage(
            'Test2',
            'Test2',
            'email',
            'letter',
            'a6',
            'test@test.com',
            'Testy McTestface',
            'OPG104',
            '96582147',
            'HW'
        );

        $rawData = [
            'ReceiptHandle' => 'handle-12345',
            'Body' => json_encode($rawBody),
        ];
        $expectedResult = SendToNotify::fromArray(
            [
                'id' => $rawData['ReceiptHandle'],
                'uuid' => $rawBody['message']['uuid'],
                'filename' => $rawBody['message']['filename'],
                'documentId' => $rawBody['message']['documentId'],
                'recipientEmail' => $rawBody['message']['recipientEmail'],
                'recipientName' => $rawBody['message']['recipientName'],
                'clientFirstName' => $rawBody['message']['clientFirstName'],
                'clientSurname' => $rawBody['message']['clientSurname'],
                'sendBy' => $rawBody['message']['sendBy'],
                'letterType' => $rawBody['message']['letterType'],
                'pendingOrDueReportType' => $rawBody['message']['pendingOrDueReportType'],
                'caseNumber' => $rawBody['message']['caseNumber'],
                'replyToType' => $rawBody['message']['replyToType']
            ]
        );

        $awsResult->method('get')->with('Messages')->willReturn([$rawData]);

        $this->sqsClientMock->expects(self::once())->method('__call')->with('receiveMessage', [$config])->willReturn($awsResult);

        $sqsAdapter = new SqsAdapter($this->sqsClientMock, self::QUEUE_URL, self::DEFAULT_WAIT_TIME);

        $actualResult = $sqsAdapter->next();

        self::assertEquals($expectedResult->getId(), $actualResult->getId());
        self::assertEquals($expectedResult->getUuid(), $actualResult->getUuid());
        self::assertEquals($expectedResult->getFilename(), $actualResult->getFilename());
    }

    /**
     * @throws Exception
     */
    public function testNextReturnsNullSuccess(): void
    {
        $awsResult = $this->createMock(Result::class);
        $config = [
            'AttributeNames' => ['SentTimestamp'],
            'MaxNumberOfMessages' => 1,
            'MessageAttributeNames' => ['All'],
            'QueueUrl' => self::QUEUE_URL,
            'WaitTimeSeconds' => self::DEFAULT_WAIT_TIME,
        ];

        $awsResult->method('get')->with('Messages')->willReturn(null);

        $this->sqsClientMock->expects(self::once())->method('__call')->with('receiveMessage', [$config])->willReturn($awsResult);

        $sqsAdapter = new SqsAdapter($this->sqsClientMock, self::QUEUE_URL, self::DEFAULT_WAIT_TIME);

        $actualResult = $sqsAdapter->next();

        self::assertNull($actualResult);
    }

    /**
     * @param array<mixed> $rawMessageBody
     * @throws Exception
     */
    #[DataProvider('invalidMessageProvider')]
    public function testNextInvalidMessageThrowsExceptionFailure(array $rawMessageBody, string $errorMessage): void
    {
        $awsResult = $this->createMock(Result::class);
        $config = [
            'AttributeNames' => ['SentTimestamp'],
            'MaxNumberOfMessages' => 1,
            'MessageAttributeNames' => ['All'],
            'QueueUrl' => self::QUEUE_URL,
            'WaitTimeSeconds' => self::DEFAULT_WAIT_TIME,
        ];
        $rawData = [
            'ReceiptHandle' => 'handle-12345',
            'Body' => json_encode($rawMessageBody),
        ];

        $awsResult->method('get')->with('Messages')->willReturn([$rawData]);

        $this->sqsClientMock->method('__call')->with('receiveMessage', [$config])->willReturn($awsResult);

        $sqsAdapter = new SqsAdapter($this->sqsClientMock, self::QUEUE_URL, self::DEFAULT_WAIT_TIME);

        self::expectException(UnexpectedValueException::class);
        self::expectExceptionMessage($errorMessage);

        $sqsAdapter->next();
    }

    /**
     * @throws Exception
     */
    public function testNextExtractsTraceId(): void
    {
        $awsResult = $this->createMock(Result::class);
        $config = [
            'AttributeNames' => ['SentTimestamp'],
            'MaxNumberOfMessages' => 1,
            'MessageAttributeNames' => ['All'],
            'QueueUrl' => self::QUEUE_URL,
            'WaitTimeSeconds' => self::DEFAULT_WAIT_TIME,
        ];

        $rawBody = $this->getMessage(
            'Test2',
            'Test2',
            'email',
            'letter',
            'a6',
            'test@test.com',
            'Testy McTestface',
            'OPG104',
            '96582147',
            'HW'
        );

        $awsResult->method('get')->with('Messages')->willReturn([
            [
                'ReceiptHandle' => 'handle-12345',
                'Body' => json_encode($rawBody),
                'Attributes' => [
                    'AwsTraceHeader' => 'trace-id-12345',
                ],
            ]
        ]);

        $this->sqsClientMock->expects(self::once())->method('__call')->with('receiveMessage', [$config])->willReturn($awsResult);

        $sqsAdapter = new SqsAdapter($this->sqsClientMock, self::QUEUE_URL, self::DEFAULT_WAIT_TIME);

        $actualResult = $sqsAdapter->next();

        self::assertEquals('trace-id-12345', $actualResult->getTraceId());
    }

    /**
     * @return array<mixed>
     */
    public static function invalidMessageProvider(): array
    {
        return [
            [['message' => ['uuid' => 'asd-123']], 'Missing "sendBy"'],
            [['message' => ['sendBy' => ['method' => 'post']]], 'Missing "sendBy.documentType"'],
            [['message' => ['sendBy' => ['method' => 'post', 'documentType' => 'letter'], 'filename' => 'this_is_a_test.pdf', 'documentId' => '1234']], 'Missing "uuid"'],
            [['message' => ['sendBy' => ['method' => 'email', 'documentType' => 'something'], 'uuid' => 'asd-123', 'documentId' => '1234']], 'Missing "filename"'],
            [['message' => ['sendBy' => ['method' => 'email', 'documentType' => 'something'], 'uuid' => 'asd-123', 'filename' => 'this_is_a_test.pdf']], 'Missing "documentId"'],
            [['message' => ['sendBy' => ['method' => 'email', 'documentType' => 'letter'], 'uuid' => 'asd-123', 'filename' => 'this_is_a_test.pdf', 'documentId' => '1234', 'recipientName' => 'Beth Schwarz', 'letterType' => 'IN-4']], 'Missing "recipientEmail"'],
            [['message' => ['sendBy' => ['method' => 'email', 'documentType' => 'letter'], 'uuid' => 'asd-123', 'filename' => 'this_is_a_test.pdf', 'documentId' => '1234', 'recipientEmail' => 'test@test.com', 'letterType' => 'IN-4']], 'Missing "recipientName"'],
            [['message' => ['sendBy' => ['method' => 'email', 'documentType' => 'letter'], 'uuid' => 'asd-123', 'filename' => 'this_is_a_test.pdf', 'documentId' => '1234', 'recipientEmail' => 'test@test.com', 'recipientName' => 'Beth Schwarz']], 'Missing "letterType"'],
            [['message' => []], 'Empty message'],
            [[], 'Empty body'],
        ];
    }

    /**
     * @throws Exception
     */
    public function testDeleteSuccess(): void
    {
        $command = SendToNotify::fromArray(
            [
                'id' => 'handle-85736',
                'uuid' => 'uuid-8537',
                'filename' => 'file.pdf',
                'documentId' => '1234',
                'recipientEmail' => null,
                'recipientName' => null,
                'clientFirstName' => null,
                'clientSurname' => null,
                'sendBy' => [
                    'method' => 'post',
                    'documentType' => 'letter'
                ],
                'letterType' => null,
                'pendingOrDueReportType' => null,
                'caseNumber' => '74442574',
                'replyToType' => 'HW'
            ]
        );

        $this->sqsClientMock
            ->expects(self::once())
            ->method('__call')
            ->with(
                'deleteMessage',
                [[
                    'QueueUrl' => self::QUEUE_URL,
                    'ReceiptHandle' => $command->getId(),
                ]]
            );

        $sqsAdapter = new SqsAdapter($this->sqsClientMock, self::QUEUE_URL, self::DEFAULT_WAIT_TIME);

        $sqsAdapter->delete($command);
    }

    private function getMessage(
        ?string $clientFirstName,
        ?string $clientSurname,
        string $sendByMethod,
        string $sendByDocumentType,
        ?string $letterType,
        ?string $recipientEmail,
        ?string $recipientName,
        ?string $pendingOrDueReportType,
        ?string $caseNumber,
        ?string $replyToType
    ): array
    {
        return ['message' => [
            'uuid' => 'asd-123',
            'filename' => 'this_is_a_test.pdf',
            'documentId' => '1234',
            'recipientEmail' => $recipientEmail,
            'recipientName' => $recipientName,
            'clientFirstName' => $clientFirstName,
            'clientSurname' => $clientSurname,
            'sendBy' => [
                'method' => $sendByMethod,
                'documentType' => $sendByDocumentType
            ],
            'letterType' => $letterType,
            'pendingOrDueReportType' => $pendingOrDueReportType,
            'caseNumber' => $caseNumber,
            'replyToType' => $replyToType
        ]];
    }
}
