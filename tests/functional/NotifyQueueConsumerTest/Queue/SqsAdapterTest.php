<?php

declare(strict_types=1);

namespace NotifyQueueConsumerTest\Functional\Queue;

use Exception;
use PHPUnit\Framework\TestCase;
use Aws\Sqs\SqsClient;
use NotifyQueueConsumer\Command\Model\SendToNotify;
use NotifyQueueConsumer\Queue\SqsAdapter;

class SqsAdapterTest extends TestCase
{
    private SqsClient $awsSqsClient;
    private string $queueUrl;
    private SqsAdapter $queueAdapter;

    public function setUp(): void
    {
        global $awsSqsClient;

        parent::setUp();

        $this->awsSqsClient = $awsSqsClient;
        $queueName = md5(__CLASS__ . '_' . time());
        $createQueueResult = $this->awsSqsClient->createQueue([
            'QueueName' => $queueName,
            'Attributes' => [
                'VisibilityTimeout' => 0,
                'ReceiveMessageWaitTimeSeconds' => 0,
            ],
        ]);
        $this->queueUrl = (string)$createQueueResult->get('QueueUrl');

        // Short wait time so tests run fast...
        $waitTime = 0;
        $this->queueAdapter = new SqsAdapter($awsSqsClient, $this->queueUrl, $waitTime);
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->awsSqsClient->deleteQueue(['QueueUrl' => $this->queueUrl]);
    }

    /**
     * @throws Exception
     */
    public function testRetrieveMessage()
    {
        $uuid = (string)rand(1, 100);
        $filename = sprintf('some_file_%d.pdf', rand(1, 100));
        $documentId = rand(101, 200);

        $this->createMessage($uuid, $filename, $documentId);

        $message = $this->queueAdapter->next();

        self::assertEquals($uuid, $message->getUuid());
        self::assertEquals($filename, $message->getFilename());
        self::assertEquals($documentId, $message->getDocumentId());
        self::assertNotEmpty($message->getId());
    }

    /**
     * @throws Exception
     * @depends testRetrieveMessage
     */
    public function testDeleteMessage()
    {
        $uuid = (string)rand(1, 100);
        $filename = sprintf('some_file_%d.pdf', rand(1, 100));
        $documentId = rand(101, 200);

        $this->createMessage($uuid, $filename, $documentId);

        $message = $this->queueAdapter->next();

        self::assertNotEmpty($message);

        $this->queueAdapter->delete(SendToNotify::fromArray([
            'id' => $message->getId(),
            'documentId' => 123,
            'uuid' => 'any',
            'filename' => 'any',
        ]));

        $message = $this->queueAdapter->next();

        self::assertNull($message);
    }

    private function createMessage(string $uuid, string $filename, int $documentId): void
    {
        $this->awsSqsClient->sendMessage(
            [
                'QueueUrl' => $this->queueUrl,
                'MessageBody' => sprintf(
                    '{"uuid":"%s","filename":"%s","documentId":"%d"}',
                    $uuid,
                    $filename,
                    $documentId
                ),
            ]
        );
    }
}
