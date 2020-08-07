<?php

declare(strict_types=1);

namespace NotifyQueueConsumerTest\Functional;

use Aws\Sqs\SqsClient;
use Exception;
use NotifyQueueConsumer\Command\Model\SendToNotify;
use NotifyQueueConsumer\Queue\SqsAdapter;
use PHPUnit\Framework\TestCase;

class RetrieveQueueMessageTest extends TestCase
{
    private SqsClient $awsSqsClient;
    private string $queueName;
    private string $queueUrl;
    private SqsAdapter $queueAdapter;

    public function setUp(): void
    {
        global $awsSqsClient;

        parent::setUp();

        $this->awsSqsClient = $awsSqsClient;
        $this->queueName = md5(__CLASS__ . '_' . time());
        $createQueueResult = $this->awsSqsClient->createQueue(
            [
                'QueueName' => $this->queueName,
                'Attributes' => [
                    'VisibilityTimeout' => 0,
                    'ReceiveMessageWaitTimeSeconds' => 0
                ],
            ]
        );
        $this->queueUrl = (string)$createQueueResult->get('QueueUrl');

        $this->queueAdapter = new SqsAdapter($awsSqsClient, $this->queueUrl);
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
     */
    public function testDeleteMessage()
    {
        $uuid = (string)rand(1, 100);
        $filename = sprintf('some_file_%d.pdf', rand(1, 100));
        $documentId = rand(101, 200);

        $this->createMessage($uuid, $filename, $documentId);

        $message = $this->queueAdapter->next();

        self::assertEquals($uuid, $message->getUuid());

        $this->queueAdapter->delete(SendToNotify::fromArray([
            'id' => $message->getId(),
            'documentId' => $message->getDocumentId(),
            'uuid' => $message->getUuid(),
            'filename' => $message->getFilename(),
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
