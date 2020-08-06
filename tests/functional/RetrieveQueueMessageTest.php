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
    private string $queueUrl;
    private SqsAdapter $queueAdapter;

    public function setUp(): void
    {
        global $config; // config is set in the phpunit.xml bootstrap

        parent::setUp();

        // Custom logger so we can capture any messages
        $this->logger = $psrLoggerAdapter = new TestLogger();

        // Initialise the other services using the above config and logger
        require_once __DIR__ . '/../../src/bootstrap/services.php';
        // Imported services
        /** @var Filesystem $filesystem */
        /** @var SqsClient $awsSqsClient */
        /** @var SqsAdapter $queue */

        // TODO create queue

        $this->filesystem = $filesystem;
        $this->awsSqsClient = $awsSqsClient;
        $this->queueUrl = $config['aws']['sqs']['queue_url'];
        $this->queueAdapter = $queue;
    }

    public function tearDown(): void
    {
        parent::tearDown();

        // TODO delete queue
    }

    public function testRetrieveMessage()
    {
        $uuid = '12345';
        $filename = 'some_file.pdf';
        $documentId = 82374;

        $this->createMessage($uuid, $filename, $documentId);

        $message = $this->queueAdapter->next();

        self::assertEquals($uuid, $message->getUuid());
        self::assertEquals($filename, $message->getFilename());
        self::assertEquals($documentId, $message->getDocumentId());
        self::assertNotEmpty($message->getId());
    }

    private function createMessage(string $uuid, string $filename, int $documentId): void
    {
        $this->awsSqsClient->sendMessage([
            'QueueUrl' => $this->queueUrl,
            'MessageBody' => sprintf('{"uuid":"%s","filename":"%s","documentId":"%d"}', $uuid, $filename, $documentId),
        ]);
    }
}
