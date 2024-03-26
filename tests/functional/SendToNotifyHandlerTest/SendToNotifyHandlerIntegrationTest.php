<?php

declare(strict_types=1);

namespace SendToNotifyHandlerTest;

use Alphagov\Notifications\Client;
use Alphagov\Notifications\Client as NotifyClient;
use Aws\Sqs\SqsClient;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\HandlerStack;
use League\Flysystem\Filesystem;
use NotifyQueueConsumer\Command\Handler\SendToNotifyHandler;
use NotifyQueueConsumer\Command\Model\SendToNotify;
use NotifyQueueConsumer\Queue\SqsAdapter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;

class SendToNotifyHandlerIntegrationTest extends TestCase
{
    private Filesystem $filesystem;
    private SqsClient $awsSqsClient;
    private string $queueUrl;
    private SendToNotifyHandler $sendToNotifyHandler;
    private SendToNotify $sendToNotify;

    private LoggerInterface&MockObject $logger;

    private const TEST_FILE_PATH = __DIR__ . '/../../fixtures/sample_doc.pdf';

    public function setUp(): void
    {
        global $awsSqsClient, $config, $filesystem, $notifyClient ;

        parent::setUp();

        $this->awsSqsClient = $awsSqsClient;
        $this->filesystem = $filesystem;
        $this->createUniqueSqsQueueForThisRun();

        $this->guzzleHandlerStack = HandlerStack::create();
        $notifyGuzzleClient = new GuzzleClient(['handler' => $this->guzzleHandlerStack]);

        $notifyClient = new NotifyClient(
            [
                'apiKey' => $config['notify']['api_key'],
                'httpClient' => $notifyGuzzleClient,
                'baseUrl' => $config['notify']['base_url'],
            ]
        );

        $this->sendToNotifyHandler = new SendToNotifyHandler($filesystem, $notifyClient);
        $this->logger = $this->createMock(LoggerInterface::class);

    }

    public function testCanReturnErrorMessage(): void
    {
        $uuidForDoc = (string)Uuid::uuid4();
        $docId = 1234;
        $data = $this->getData('email', 'letter', 'bs1', 'HW', $uuidForDoc, (string)$docId);

        $this->sendToNotify = SendToNotify::fromArray($data);

        $this->createMessageWithFile($uuidForDoc, 1234, 'bs1', 'email', 'letter', 'HW');

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

        $result = $this->sendToNotifyHandler->handle($this->sendToNotify);
        self::assertEquals($result, 'Kate');
    }

    private function getData(string $sendByMethod, string $sendByDocType, string $letterType, string $replyToType, string $uuid, string $docId): array
    {
        return [
            'id' => '123',
            'uuid' => $uuid,
            'filename' => self::TEST_FILE_PATH,
            'documentId' => $docId,
            'recipientEmail' => 'test@test.com',
            'recipientName' => 'Test name',
            'clientFirstName' => 'Test2',
            'clientSurname' => 'Test Surname',
            'sendBy' => [
                'method' => $sendByMethod,
                'documentType' => $sendByDocType
            ],
            'letterType' => $letterType,
            'pendingOrDueReportType' => 'OPG103',
            'caseNumber' => '74442574',
            'replyToType' => $replyToType
        ];
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
}