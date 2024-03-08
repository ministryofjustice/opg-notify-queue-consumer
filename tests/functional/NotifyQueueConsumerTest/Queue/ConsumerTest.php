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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;

class ConsumerTest extends TestCase
{
    private const TEST_FILE_PATH = __DIR__ . '/../../../fixtures/sample_doc.pdf';
    private Filesystem $filesystem;
    private SqsClient $awsSqsClient;
    private string $queueUrl;
    private Consumer $consumer;
    private LoggerInterface&MockObject $logger;
    private HandlerStack $guzzleHandlerStack;

    public function setUp(): void
    {
        global $awsSqsClient,
        $filesystem,
        $config,
        $sendToNotifyHandler,
        $updateDocumentStatusHandler;

        parent::setUp();

        $this->awsSqsClient = $awsSqsClient;
        $this->filesystem = $filesystem;

        $this->createUniqueSqsQueueForThisRun();

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
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->consumer = new Consumer(
            $queueAdapter,
            $sendToNotifyHandler,
            $updateDocumentStatusHandler,
            $this->logger,
            function () {},
        );
    }

    // This is shared between tests in a run, so failure to pick up a message may affect other tests in the same run, but not subsequent ones
    private function createUniqueSqsQueueForThisRun(): void
    {
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
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->awsSqsClient->deleteQueue(['QueueUrl' => $this->queueUrl]);
    }

    public static function messageProvider(): array
    {
        return [
            ['bs1', 'email', 'letter', 'HW'],
            ['bs2', 'email', 'letter', 'PFA LAY'],
            ['rd1', 'email', 'letter', 'PFA PRO'],
            ['rd2', 'email', 'letter', 'PFA PA'],
            ['ri2', 'post', 'letter', null],
            ['ri3', 'email', 'letter', 'HW'],
            ['rr1', 'email', 'letter', 'PFA LAY'],
            ['rr2', 'email', 'letter', 'PFA PRO'],
            ['rr3','email', 'letter', 'PFA PA'],
            ['a6','email', 'letter', 'FINANCE'],
            ['af1','email', 'letter', 'FINANCE'],
            ['af2','email', 'letter', 'FINANCE'],
            ['af3','email', 'letter', 'FINANCE'],
            ['fn14','email', 'letter', 'FINANCE'],
            [null, 'post', 'letter', null]

        ];
    }

    /**
     * @@dataProvider  messageProvider
     */
    public function testRunNotifySendUpdateStatusSuccess(?string $templateId, string $sendByMethod, string $sendByDocumentType, ?string $replyToType)
    {
        $this->createMessageWithFile((string)Uuid::uuid4(), 1234, $templateId, $sendByMethod, $sendByDocumentType, $replyToType);

        $this->awsSqsClient->getQueueAttributes(['QueueUrl' => $this->queueUrl]);

        $expectedLogMessageSequence = [
            'Asking for next message',
            'Sending to Notify',
            'Deleting processed message',
            'Updating document status',
            'Success',
        ];


        $this->logger
            ->expects($this->exactly(count($expectedLogMessageSequence)))
            ->method('info')
            ->with($this->callback(fn ($msg) => in_array($msg, $expectedLogMessageSequence)));

        $this->consumer->run();
    }

    /**
     * @throws Exception
     */
    public function testRunNotifyValidationFailureUpdateStatusSuccess()
    {
        // Modify the uri so we tell the Prism mock server which response we want
        $this->guzzleHandlerStack->push(GuzzleMiddleware::mapRequest(function (RequestInterface $request) {
            $method = $request->getMethod();

            if (str_contains((string)$request->getUri(), '/v2/notifications/') && $method === 'GET') {
                $request = $request->withAddedHeader('Prefer', 'example=validation-failed');
            }
            return $request;
        }));
        $this->createMessageWithFile((string)Uuid::uuid4(), 1234, 'rd1', 'email', 'letter', 'HW');

        $this->awsSqsClient->getQueueAttributes(['QueueUrl' => $this->queueUrl]);

        $expectedLogMessageSequence = [
            'Asking for next message',
            'Sending to Notify',
            'Deleting processed message',
            'Updating document status',
            'Success',
        ];

        $this->logger
            ->expects($this->exactly(count($expectedLogMessageSequence)))
            ->method('info')
            ->with($this->callback(fn ($msg) => in_array($msg, $expectedLogMessageSequence)));

        $this->consumer->run();
    }

    /**
     * @throws Exception
     */
    public function testRunNotifyValidationFailureExceptionFailure()
    {
        // Modify the uri so we tell the Prism mock server which response we want
        $this->guzzleHandlerStack->push(GuzzleMiddleware::mapRequest(function (RequestInterface $request) {
            $method = $request->getMethod();

            if (str_contains((string)$request->getUri(), '/v2/notifications/letter') && $method === 'POST') {
                $request = $request->withAddedHeader('Prefer', 'code=400');
            }
            return $request;
        }));
        $this->createMessageWithFile((string)Uuid::uuid4(), 1234, null, 'post', 'letter', 'HW');

        $this->awsSqsClient->getQueueAttributes(['QueueUrl' => $this->queueUrl]);

        $expectedLogMessageSequence = [
            'Asking for next message',
            'Sending to Notify',
        ];

        $this->logger
            ->expects($this->exactly(count($expectedLogMessageSequence)))
            ->method('info')
            ->with($this->callback(fn ($msg) => in_array($msg, $expectedLogMessageSequence)));

        $this->logger
            ->expects($this->once())
            ->method('critical')
            ->with('Error processing message');

        $this->consumer->run();
    }

    /**
     * @throws Exception
     */
    public function testRunNotifyMessageExistsDuplicateFailure()
    {
        // Modify the uri so we tell the Prism mock server which response we want
        $this->guzzleHandlerStack->push(GuzzleMiddleware::mapRequest(function (RequestInterface $request) {
            $method = $request->getMethod();

            if (str_contains((string)$request->getUri(), '/v2/notifications?reference=') && $method === 'GET') {
                $request = $request->withAddedHeader('Prefer', 'example=one');
            }

            return $request;
        }));
        $this->createMessageWithFile((string)Uuid::uuid4(), 1234, 'rd1', 'email', 'letter', 'HW');

        $this->awsSqsClient->getQueueAttributes(['QueueUrl' => $this->queueUrl]);

        $expectedLogMessageSequence = [
            'Asking for next message',
            'Sending to Notify',
            'Deleting duplicate message',
        ];

        $this->logger
            ->expects($this->exactly(count($expectedLogMessageSequence)))
            ->method('info')
            ->with($this->callback(fn ($msg) => in_array($msg, $expectedLogMessageSequence)));

        $this->consumer->run();
    }

    private function createMessageWithFile(string $uuid, int $documentId, ?string $letterType, string $sendByMethod, string $sendByDocumentType, ?string $replyToType): void
    {
        $content = file_get_contents(self::TEST_FILE_PATH);
        $destination = basename(self::TEST_FILE_PATH);

        $this->filesystem->write($destination, $content);

        $this->awsSqsClient->sendMessage(
            [
                'QueueUrl' => $this->queueUrl,
                'MessageBody' => sprintf(
                    '{"message":{"uuid":"%s","filename":"%s","documentId":"%d",
                    "recipientEmail":"%s", "recipientName":"%s", "clientFirstName":"%s", "clientSurname":"%s",
                    "sendBy":{"method": "%s", "documentType": "%s"}, "letterType":"%s", "pendingOrDueReportType":"%s",
                     "caseNumber":"%s", "replyToType":"%s"}}',
                    $uuid,
                    $destination,
                    $documentId,
                    'test@test.com',
                    'Test name',
                    'Test2',
                    'Test Surname',
                    $sendByMethod,
                    $sendByDocumentType,
                    $letterType,
                    'OPG103',
                    '74442574',
                    $replyToType
                ),
            ]
        );
    }
}
