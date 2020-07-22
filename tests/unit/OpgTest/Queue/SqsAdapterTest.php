<?php

declare(strict_types=1);

namespace OpgTest\Queue;

use Exception;
use Opg\Command\SendToNotify;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Aws\Result;
use Aws\Sqs\SqsClient;
use Opg\Queue\SqsAdapter;

class SqsAdapterTest extends TestCase
{
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
    public function testNextReturnsMessageSuccess(): void
    {
        $awsResult = $this->createMock(Result::class);
        $config = [
            'AttributeNames' => ['SentTimestamp'],
            'MaxNumberOfMessages' => 1,
            'MessageAttributeNames' => ['All'],
            'QueueUrl' => $this->queueUrl,
            'WaitTimeSeconds' => 0,
        ];
        $rawBody = ['uuid' => 'asd-123', 'filename' => 'this_is_a_test.pdf'];
        $rawData = [
            'ReceiptHandle' => 'handle-12345',
            'Body' => json_encode($rawBody),
        ];
        $expectedResult = SendToNotify::fromArray([
            'id' => $rawData['ReceiptHandle'],
            'uuid' => $rawBody['uuid'],
            'filename' => $rawBody['filename'],
        ]);

        $awsResult->method('get')->with('Messages')->willReturn([$rawData]);

        $this->sqsClientMock->expects(self::once())->method('receiveMessage')->with($config)->willReturn($awsResult);

        $sqsAdapter = new SqsAdapter($this->sqsClientMock, $this->queueUrl);

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
            'WaitTimeSeconds' => 0,
        ];

        $awsResult->method('get')->with('Messages')->willReturn(null);

        $this->sqsClientMock->expects(self::once())->method('receiveMessage')->with($config)->willReturn($awsResult);

        $sqsAdapter = new SqsAdapter($this->sqsClientMock, $this->queueUrl);

        $actualResult = $sqsAdapter->next();

        self::assertNull($actualResult);
    }

    /**
     * @throws Exception
     */
    public function testDeleteSuccess(): void
    {
        $command = SendToNotify::fromArray([
            'id' => 'handle-85736',
            'uuid' => 'uuid-8537',
            'filename' => 'file.pdf',
        ]);

        $this->sqsClientMock
            ->expects(self::once())
            ->method('deleteMessage')
            ->with([
                'QueueUrl' => $this->queueUrl,
                'ReceiptHandle' => $command->getId(),
            ]);

        $sqsAdapter = new SqsAdapter($this->sqsClientMock, $this->queueUrl);

        $sqsAdapter->delete($command);
    }
}
