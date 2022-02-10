<?php

declare(strict_types=1);

namespace NotifyQueueConsumerTest\Unit\Queue;

use Exception;
use NotifyQueueConsumer\Command\Model\SendToNotify;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Aws\Result;
use Aws\Sqs\SqsClient;
use NotifyQueueConsumer\Queue\SqsAdapter;
use UnexpectedValueException;

class SqsAdapterTest extends TestCase
{
    private const DEFAULT_WAIT_TIME = 0;
    /**
     * @var SqsClient|MockObject
     */
    private $sqsClientMock;
    private string $queueUrl = 'url...';

    public function setUp(): void
    {
        // The AWS SDK uses magic methods so we need a workaround to mock them
        $this->sqsClientMock = $this
            ->getMockBuilder(SqsClient::class)
            ->disableOriginalConstructor()
            ->addMethods(['receiveMessage', 'deleteMessage'])
            ->getMock();
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
            'QueueUrl' => $this->queueUrl,
            'WaitTimeSeconds' => self::DEFAULT_WAIT_TIME,
        ];

        $rawBody = $this->getMessage(null, null, 'post', 'letter', null, null, null);

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
            ]
        );

        $awsResult->method('get')->with('Messages')->willReturn([$rawData]);

        $this->sqsClientMock->expects(self::once())->method('receiveMessage')->with($config)->willReturn($awsResult);

        $sqsAdapter = new SqsAdapter($this->sqsClientMock, $this->queueUrl, self::DEFAULT_WAIT_TIME);

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
            'QueueUrl' => $this->queueUrl,
            'WaitTimeSeconds' => self::DEFAULT_WAIT_TIME,
        ];
        $rawBody = $this->getMessage(null, null, 'post', 'letter', null, null, null);

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
            ]
        );

        $awsResult->method('get')->with('Messages')->willReturn([$rawData]);

        $this->sqsClientMock->expects(self::once())->method('receiveMessage')->with($config)->willReturn($awsResult);

        $sqsAdapter = new SqsAdapter($this->sqsClientMock, $this->queueUrl, self::DEFAULT_WAIT_TIME);

        $actualResult = $sqsAdapter->next();

        self::assertEquals($expectedResult->getId(), $actualResult->getId());
        self::assertEquals($expectedResult->getUuid(), $actualResult->getUuid());
        self::assertEquals($expectedResult->getFilename(), $actualResult->getFilename());
    }

    /**
     * @throws Exception
     */
    public function testNextReturnsMessageSuccessEmailInvoice(): void
    {
        $awsResult = $this->createMock(Result::class);
        $config = [
            'AttributeNames' => ['SentTimestamp'],
            'MaxNumberOfMessages' => 1,
            'MessageAttributeNames' => ['All'],
            'QueueUrl' => $this->queueUrl,
            'WaitTimeSeconds' => self::DEFAULT_WAIT_TIME,
        ];

        $rawBody = $this->getMessage('Test2', 'Test2', 'email', 'invoice', 'a6', 'test@test.com', 'Testy McTestface');

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
            ]
        );

        $awsResult->method('get')->with('Messages')->willReturn([$rawData]);

        $this->sqsClientMock->expects(self::once())->method('receiveMessage')->with($config)->willReturn($awsResult);

        $sqsAdapter = new SqsAdapter($this->sqsClientMock, $this->queueUrl, self::DEFAULT_WAIT_TIME);

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
            'QueueUrl' => $this->queueUrl,
            'WaitTimeSeconds' => self::DEFAULT_WAIT_TIME,
        ];

        $rawBody = $this->getMessage('Test2', 'Test2', 'email', 'letter', 'a6', 'test@test.com', 'Testy McTestface');

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
            ]
        );

        $awsResult->method('get')->with('Messages')->willReturn([$rawData]);

        $this->sqsClientMock->expects(self::once())->method('receiveMessage')->with($config)->willReturn($awsResult);

        $sqsAdapter = new SqsAdapter($this->sqsClientMock, $this->queueUrl, self::DEFAULT_WAIT_TIME);

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
            'QueueUrl' => $this->queueUrl,
            'WaitTimeSeconds' => self::DEFAULT_WAIT_TIME,
        ];

        $awsResult->method('get')->with('Messages')->willReturn(null);

        $this->sqsClientMock->expects(self::once())->method('receiveMessage')->with($config)->willReturn($awsResult);

        $sqsAdapter = new SqsAdapter($this->sqsClientMock, $this->queueUrl, self::DEFAULT_WAIT_TIME);

        $actualResult = $sqsAdapter->next();

        self::assertNull($actualResult);
    }

    /**
     * @param array<mixed> $rawMessageBody
     * @throws Exception
     * @dataProvider invalidMessageProvider
     */
    public function testNextInvalidMessageThrowsExceptionFailure(array $rawMessageBody): void
    {
        $awsResult = $this->createMock(Result::class);
        $config = [
            'AttributeNames' => ['SentTimestamp'],
            'MaxNumberOfMessages' => 1,
            'MessageAttributeNames' => ['All'],
            'QueueUrl' => $this->queueUrl,
            'WaitTimeSeconds' => self::DEFAULT_WAIT_TIME,
        ];
        $rawData = [
            'ReceiptHandle' => 'handle-12345',
            'Body' => json_encode($rawMessageBody),
        ];

        $awsResult->method('get')->with('Messages')->willReturn([$rawData]);

        $this->sqsClientMock->method('receiveMessage')->with($config)->willReturn($awsResult);

        $sqsAdapter = new SqsAdapter($this->sqsClientMock, $this->queueUrl, self::DEFAULT_WAIT_TIME);

        self::expectException(UnexpectedValueException::class);

        $sqsAdapter->next();
    }

    /**
     * @return array<mixed>
     */
    public function invalidMessageProvider(): array
    {
        return [
            [['message' => ['filename' => 'this_is_a_test.pdf', 'documentId' => '1234']]],
            [['message' => ['uuid' => 'asd-123', 'documentId' => '1234']]],
            [['message' => ['uuid' => 'asd-123', 'filename' => 'this_is_a_test.pdf']]],
            [['message' => []]],
            [[]],
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
            ]
        );

        $this->sqsClientMock
            ->expects(self::once())
            ->method('deleteMessage')
            ->with(
                [
                    'QueueUrl' => $this->queueUrl,
                    'ReceiptHandle' => $command->getId(),
                ]
            );

        $sqsAdapter = new SqsAdapter($this->sqsClientMock, $this->queueUrl, self::DEFAULT_WAIT_TIME);

        $sqsAdapter->delete($command);
    }

    private function getMessage(?string $clientFirstName, ?string $clientSurname, string $sendByMethod, string $sendByDocumentType, ?string $letterType, ?string $recipientEmail, ?string $recipientName): array {

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
        ]];
    }
}
