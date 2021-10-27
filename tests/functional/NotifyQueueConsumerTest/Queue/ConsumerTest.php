<?php

declare(strict_types=1);

namespace NotifyQueueConsumerTest\Functional\Queue;

use Alphagov\Notifications\Client as NotifyClient;
use Aws\Sqs\SqsClient;
use Exception;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware as GuzzleMiddleware;
use League\Flysystem\Filesystem;
use NotifyQueueConsumer\Command\Handler\SendToNotifyHandler;
use NotifyQueueConsumer\Queue\Consumer;
use NotifyQueueConsumer\Queue\SqsAdapter;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Log\Test\TestLogger;
use Ramsey\Uuid\Uuid;

class ConsumerTest extends TestCase
{
    private const TEST_FILE_PATH = __DIR__ . '/../../../fixtures/sample_doc.pdf';
    private Filesystem $filesystem;
    private SqsClient $awsSqsClient;
    private string $queueUrl;
    private Consumer $consumer;
    private TestLogger $logger;
    private HandlerStack $guzzleHandlerStack;

    public function setUp(): void
    {
        global $awsSqsClient,
               $filesystem,
               $awsS3Client,
               $config,
               $sendToNotifyHandler,
               $updateDocumentStatusHandler;

        parent::setUp();

        $this->awsSqsClient = $awsSqsClient;
        $queueName = md5(__CLASS__ . '_' . time());
        $createQueueResult = $this->awsSqsClient
            ->createQueue(
                [
                    'QueueName' => $queueName,
                    'Attributes' => [
                        'VisibilityTimeout' => 0,
                        'ReceiveMessageWaitTimeSeconds' => 0,
                    ],
                ]
            );
        $this->queueUrl = (string)$createQueueResult->get('QueueUrl');
        $this->filesystem = $filesystem;

        if (!$awsS3Client->doesBucketExist($config['aws']['s3']['bucket'])) {
            $awsS3Client
                ->createBucket(
                    [
                        'Bucket' => $config['aws']['s3']['bucket'],
                    ]
                );
        }

        // Short wait time so tests run fast...
        $waitTime = 0;
        $queueAdapter = new SqsAdapter($this->awsSqsClient, $this->queueUrl, $waitTime);

        $this->guzzleHandlerStack = HandlerStack::create();

        $notifyGuzzleClient = new GuzzleClient(['handler' => $this->guzzleHandlerStack]);
        $notifyClient = new NotifyClient(
            [
                'apiKey' => $config['notify']['api_key'],
                'httpClient' => $notifyGuzzleClient,
                'baseUrl' => $config['notify']['base_url'],
            ]
        );
        $sendToNotifyHandler = new SendToNotifyHandler(
            $filesystem,
            $notifyClient
        );
        $this->logger = new TestLogger();

        $this->consumer = new Consumer(
            $queueAdapter,
            $sendToNotifyHandler,
            $updateDocumentStatusHandler,
            $this->logger
        );
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->awsSqsClient->deleteQueue(['QueueUrl' => $this->queueUrl]);
    }

    /**
     * @throws Exception
     */
    public function testRunNotifyValidationFailureUpdateStatusSuccess()
    {
        // Modify the uri so we tell the Prism mock server which response we want
        $this->guzzleHandlerStack->push(GuzzleMiddleware::mapRequest(function (RequestInterface $request) {
            $method = $request->getMethod();

            if (strpos((string)$request->getUri(), '/v2/notifications/') !== false && $method === 'GET') {
                $request = $request->withAddedHeader('Prefer', 'example=validation-failed');
            }
            return $request;
        }));
        $this->createMessageWithFile((string)Uuid::uuid4(), 1234);

        $this->awsSqsClient->getQueueAttributes(['QueueUrl' => $this->queueUrl]);

        $expectedLogMessageSequence = [
            'Asking for next message',
            'Sending to Notify',
            'Deleting processed message',
            'Updating document status',
            'Success',
        ];

        $this->consumer->run();

        self::assertNotEmpty($this->logger->records);

        foreach ($expectedLogMessageSequence as $i => $expectedMessage) {
            self::assertEquals(
                $expectedMessage,
                $this->logger->records[$i]['message'],
                var_export($this->logger->records, true)
            );
        }
    }

    /**
     * @throws Exception
     */
    public function testRunNotifyValidationFailureExceptionFailure()
    {
        // Modify the uri so we tell the Prism mock server which response we want
        $this->guzzleHandlerStack->push(GuzzleMiddleware::mapRequest(function (RequestInterface $request) {
            $method = $request->getMethod();

            if (strpos((string)$request->getUri(), '/v2/notifications/letter') !== false && $method === 'POST') {
                $request = $request->withAddedHeader('Prefer', 'code=400');
            }
            return $request;
        }));
        $this->createMessageWithFile((string)Uuid::uuid4(), 1234);

        $this->awsSqsClient->getQueueAttributes(['QueueUrl' => $this->queueUrl]);

        $expectedLogMessageSequence = [
            'Asking for next message',
            'Sending to Notify',
            'Error processing message',
        ];

        $this->consumer->run();

        self::assertNotEmpty($this->logger->records);

        foreach ($expectedLogMessageSequence as $i => $expectedMessage) {
            self::assertEquals(
                $expectedMessage,
                $this->logger->records[$i]['message'],
                var_export($this->logger->records, true)
            );
        }
    }

    /**
     * @throws Exception
     */
    public function testRunNotifyMessageExistsDuplicateFailure()
    {
        // Modify the uri so we tell the Prism mock server which response we want
        $this->guzzleHandlerStack->push(GuzzleMiddleware::mapRequest(function (RequestInterface $request) {
            $method = $request->getMethod();

            if (strpos((string)$request->getUri(), '/v2/notifications?reference=') !== false && $method === 'GET') {
                $request = $request->withAddedHeader('Prefer', 'example=one');
            }

            return $request;
        }));
        $this->createMessageWithFile((string)Uuid::uuid4(), 1234);

        $this->awsSqsClient->getQueueAttributes(['QueueUrl' => $this->queueUrl]);

        $expectedLogMessageSequence = [
            'Asking for next message',
            'Sending to Notify',
            'Deleting duplicate message',
        ];

        $this->consumer->run();

        self::assertNotEmpty($this->logger->records);

        foreach ($expectedLogMessageSequence as $i => $expectedMessage) {
            self::assertEquals(
                $expectedMessage,
                $this->logger->records[$i]['message'],
                var_export($this->logger->records, true)
            );
        }
    }

    private function createMessageWithFile(string $uuid, int $documentId): void
    {
        $content = file_get_contents(self::TEST_FILE_PATH);
        $destination = basename(self::TEST_FILE_PATH);

        $this->filesystem->put($destination, $content);

        $this->awsSqsClient->sendMessage(
            [
                'QueueUrl' => $this->queueUrl,
                'MessageBody' => sprintf(
                    '{"message":{"uuid":"%s","filename":"%s","documentId":"%d", "documentType":"%s", 
                    "recipientEmail":"%s", "recipientName":"%s", "clientFirstName":"%s", "clientSurname":"%s",
                    "sendBy":{"method": "post", "documentType": "letter"}}}',
                    $uuid,
                    $destination,
                    $documentId,
                    null,
                    '',
                    'Test name',
                    'Test2',
                    'Test Surname'
                ),
            ]
        );
    }
}
