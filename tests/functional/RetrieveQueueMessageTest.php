<?php

declare(strict_types=1);

namespace NotifyQueueConsumerTest\Functional;

use Aws\Sqs\SqsClient;
use League\Flysystem\Filesystem;
use NotifyQueueConsumer\Queue\SqsAdapter;
use PHPUnit\Framework\TestCase;
use Psr\Log\Test\TestLogger;

class RetrieveQueueMessageTest extends TestCase
{
    private TestLogger $logger;
    private Filesystem $filesystem;
    private SqsClient $awsSqsClient;
    private string $queueName;
    private string $queueUrl;
    private SqsAdapter $queueAdapter;

    public function setUp(): void
    {
        parent::setUp();

        // Custom logger so we can capture any messages
        $this->logger = $psrLoggerAdapter = new TestLogger();

        // Initialise the other services using the above config and logger
        $config = require_once __DIR__ . '/../../src/bootstrap/config.php';
        require_once __DIR__ . '/../../src/bootstrap/services.php';
        // Imported services
        /** @var Filesystem $filesystem */
        /** @var SqsClient $awsSqsClient */
        /** @var SqsAdapter $queue */


        $this->filesystem = $filesystem;
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
