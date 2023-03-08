<?php

declare(strict_types=1);

namespace NotifyQueueConsumerTest\Unit\Command\Handler;

use Alphagov\Notifications\Exception as NotifyException;
use Exception;
use JetBrains\PhpStorm\ArrayShape;
use NotifyQueueConsumer\Queue\DuplicateMessageException;
use Psr\Http\Message\ResponseInterface;
use UnexpectedValueException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\Filesystem;
use Alphagov\Notifications\Client;
use NotifyQueueConsumer\Command\Model\SendToNotify;
use NotifyQueueConsumer\Command\Handler\SendToNotifyHandler;

class SendToNotifyHandlerTest extends TestCase
{
    private Filesystem|MockObject $mockFilesystem;
    private Client|MockObject $mockNotifyClient;
    private SendToNotifyHandler $handler;

    public function setUp(): void
    {
        parent::setUp();

        $this->mockFilesystem = $this->createMock(Filesystem::class);
        $this->mockNotifyClient = $this->createMock(Client::class);
        $this->handler = new SendToNotifyHandler(
            $this->mockFilesystem,
            $this->mockNotifyClient
        );
    }

    /**
     * @throws FileNotFoundException
     */
    public function testRetrieveQueueMessageSendToNotifyPostLetterAndReturnCommandSuccess(): void
    {
        $data = $this->getData('post', 'letter', null, null);

        $contents = "pdf content";
        $response = [
            "id" => "740e5834-3a29-46b4-9a6f-16142fde533a",
            "reference" => $data['uuid'],
            "postage" => "first",
        ];
        $notifyId = "740e5834-3a29-46b4-9a6f-16142fde533a";
        $notifyStatus = "sending";
        $statusResponse = [
            "id" => $notifyId,
            "reference" => $data['uuid'],
            "line_1" => "742 Evergreen Terrace ",
            "line_2" => "Springfield",
            "postcode" => "S1M 2SO",
            "type" => "letter",
            "status" => $notifyStatus,
            "body" => "",
            "created_at" => "",
            "created_by_name" => "",
            "sent_at" => "",
            "completed_at" => "",
        ];
        $payload = [
            "documentId" => $data['documentId'],
            "notifySendId" => $notifyId,
            "notifyStatus" => $notifyStatus,
        ];
        $command = SendToNotify::fromArray($data);

        $this->fileSystemWillReturn($data['filename'], $contents);

        $this->mockNotifyClient
            ->expects(self::once())
            ->method('sendPrecompiledLetter')
            ->with($data['uuid'], $contents)
            ->willReturn($response);

        $this->notifyClientAndAssertions($response, $statusResponse, $command, $payload);
    }

    #[ArrayShape([
        'A6 Template' => "array",
        'AF1 Template' => "array",
        'AF2 Template' => "array",
        'AF3 Template' => "array"
    ])] public function financeInvoiceLetterData(): array
    {
        return [
            'A6 Template' => ['a6', SendToNotifyHandler::NOTIFY_TEMPLATE_DOWNLOAD_A6_INVOICE],
            'AF1 Template' => ['af1', SendToNotifyHandler::NOTIFY_TEMPLATE_DOWNLOAD_AF_INVOICE],
            'AF2 Template' => ['af2', SendToNotifyHandler::NOTIFY_TEMPLATE_DOWNLOAD_AF_INVOICE],
            'AF3 Template' => ['af3', SendToNotifyHandler::NOTIFY_TEMPLATE_DOWNLOAD_AF_INVOICE],
        ];
    }

    /**
     * @dataProvider financeInvoiceLetterData
     * @throws FileNotFoundException
     */
    public function testRetrieveQueueMessageSendToNotifyEmailInvoiceAndReturnCommandExpected(string $letterType, string $letterTemplate): void
    {
        $data = $this->getData('email', 'letter', $letterType, null);

        $this->setupForInvoiceAndLettersWithAssertions($data, $letterTemplate);
    }

    #[ArrayShape([
        'BS1 Template' => "array",
        'BS2 Template' => "array",
        'FN14 Template' => "array",
        'RD1 Template' => "array",
        'RD2 Template' => "array",
        'RI2 Template' => "array",
        'RI3 Template' => "array",
        'RR1 Template' => "array",
        'RR2 Template' => "array",
        'RR3 Template' => "array"
    ])] public function annualReportLetterData(): array
    {
        return [
            'BS1 Template' => ['bs1', SendToNotifyHandler::NOTIFY_TEMPLATE_DOWNLOAD_BS1_LETTER, 'HW'],
            'BS2 Template' => ['bs2', SendToNotifyHandler::NOTIFY_TEMPLATE_DOWNLOAD_BS2_LETTER, 'HW'],
            'FN14 Template' => ['fn14', SendToNotifyHandler::NOTIFY_TEMPLATE_DOWNLOAD_FN14_LETTER, null],
            'RD1 Template' => ['rd1', SendToNotifyHandler::NOTIFY_TEMPLATE_DOWNLOAD_RD1_LETTER, 'PFA LAY'],
            'RD2 Template' => ['rd2', SendToNotifyHandler::NOTIFY_TEMPLATE_DOWNLOAD_RD2_LETTER, 'PFA PRO'],
            'RI2 Template' => ['ri2', SendToNotifyHandler::NOTIFY_TEMPLATE_DOWNLOAD_RI2_LETTER, 'PFA PA'],
            'RI3 Template' => ['ri3', SendToNotifyHandler::NOTIFY_TEMPLATE_DOWNLOAD_RI3_LETTER, 'PFA'],
            'RR1 Template' => ['rr1', SendToNotifyHandler::NOTIFY_TEMPLATE_DOWNLOAD_RR1_LETTER, 'HW'],
            'RR2 Template' => ['rr2', SendToNotifyHandler::NOTIFY_TEMPLATE_DOWNLOAD_RR2_LETTER, 'HW'],
            'RR3 Template' => ['rr3', SendToNotifyHandler::NOTIFY_TEMPLATE_DOWNLOAD_RR3_LETTER, 'HW'],
        ];
    }

    /**
     * @dataProvider annualReportLetterData
     * @throws FileNotFoundException
     */
    public function testRetrieveQueueMessageSendToNotifyEmailLetterAndReturnCommandExpected(string $letterType, string $letterTemplate, ?string $replyToType): void
    {
        $data = $this->getData('email', 'letter', $letterType, $replyToType);

        $this->setupForInvoiceAndLettersWithAssertions($data, $letterTemplate);
    }

    /**
     * @param Exception $notifyException
     * @throws FileNotFoundException
     * @dataProvider notifyExceptionProvider
     */
    public function testSendToNotifyBubblesUpApiExceptionFailure(Exception $notifyException): void
    {
        $data = $this->getData('post', 'letter', null, null);

        $contents = "some content";
        $command = SendToNotify::fromArray($data);

        $this->mockFilesystem
            ->expects(self::once())
            ->method('read')
            ->with($data['filename'])
            ->willReturn($contents);

        // https://docs.notifications.service.gov.uk/php.html#send-a-precompiled-letter-error-codes
        $this->mockNotifyClient
            ->expects(self::once())
            ->method('sendPrecompiledLetter')
            ->willThrowException($notifyException);

        self::expectException(get_class($notifyException));

        $this->handler->handle($command);
    }

    /**
     * @return array<array<Exception>>
     */
    public function notifyExceptionProvider(): array
    {
        $httpResponse = $this->createMock(ResponseInterface::class);

        return [
            [
                new NotifyException\ApiException(
                    "ValidationError",
                    400,
                    [
                        'errors' => [
                            [
                                'error' => 'ValidationError',
                                'message' => 'postage invalid. It must be either first or second.'
                            ]
                        ]
                    ],
                    $httpResponse
                )
            ],
            [new NotifyException\NotifyException('something went wrong')],
            [new NotifyException\UnexpectedValueException('something went wrong')],
        ];
    }

    /**
     * @throws FileNotFoundException
     */
    public function testRetrieveDuplicateMessageThrowsExceptionFailure(): void
    {
        $data = $this->getData('post', 'letter', 'a6', 'HW');

        $response = [
            "reference" => $data['uuid']
        ];
        $notifyId = "740e5834-3a29-46b4-9a6f-16142fde533a";
        $command = SendToNotify::fromArray($data);

        $this->mockFilesystem->expects(self::never())->method('read');
        $this->mockNotifyClient->expects(self::never())->method('sendPrecompiledLetter');
        $this->mockNotifyClient->expects(self::never())->method('getNotification');

        $this->mockNotifyClient
            ->expects(self::once())
            ->method('listNotifications')
            ->with(['reference' => $response['reference']])
            ->willReturn(
                [
                    'notifications' => [
                        ['id' => $notifyId],
                    ],
                ]
            );

        self::expectException(DuplicateMessageException::class);

        $this->handler->handle($command);
    }

    /**
     * @throws FileNotFoundException
     */
    public function testEmptyPdfInQueueFailure(): void
    {
        $data = $this->getData('post', 'letter', null, null);

        $command = SendToNotify::fromArray($data);

        $this->mockFilesystem->method('read')->willReturn(false);

        self::expectException(UnexpectedValueException::class);
        self::expectExceptionMessage("Cannot read PDF");

        $this->handler->handle($command);
    }

    /**
     * @throws FileNotFoundException
     */
    public function testNotifyResponseIdNotFoundFailure(): void
    {
        $data = $this->getData('post', 'letter', null, null);

        $contents = "some content";

        $response = [
            "reference" => "unique_ref123",
            "postage" => "first"
        ];

        $command = SendToNotify::fromArray($data);

        $this->mockFilesystem
            ->expects(self::once())
            ->method('read')
            ->with($data['filename'])
            ->willReturn($contents);

        $this->mockNotifyClient
            ->expects(self::once())
            ->method('sendPrecompiledLetter')
            ->with($data['uuid'], $contents)
            ->willReturn($response);

        self::expectException(UnexpectedValueException::class);
        self::expectExceptionMessage("No Notify id returned");

        $this->handler->handle($command);
    }

    /**
     * @throws FileNotFoundException
     */
    public function testNotifyStatusNotFoundFailure(): void
    {
        $data = $this->getData('post', 'letter', null, null);
        $contents = "some content";

        $response = [
            "id" => "740e5834-3a29-46b4-9a6f-16142fde533a",
            "reference" => "unique_ref123",
            "postage" => "first"
        ];

        $command = SendToNotify::fromArray($data);

        $this->mockFilesystem
            ->expects(self::once())
            ->method('read')
            ->with($data['filename'])
            ->willReturn($contents);

        $this->mockNotifyClient
            ->expects(self::once())
            ->method('sendPrecompiledLetter')
            ->with($data['uuid'], $contents)
            ->willReturn($response);

        $this->mockNotifyClient
            ->expects(self::once())
            ->method('getNotification')
            ->with($response['id'])
            ->willReturn(null);

        self::expectException(UnexpectedValueException::class);
        self::expectExceptionMessage(sprintf("No Notify status found for the ID: %s", $response['id']));

        $this->handler->handle($command);
    }

    /**
     * @throws FileNotFoundException
     */
    public function testRetrieveQueueMessageSendToNotifyEmailFailsWhenNoNotifyIdReturned(): void
    {
       $data = $this->getData('email', 'letter', 'a6', 'HW');

        $contents = "pdf content";

        $prepareUploadResponse = [
            'file' => 'cGRmIGNvbnRlbnQ=',
            'is_csv' => false
        ];

        $response = [
            "reference" => $data['uuid']
        ];


        $command = SendToNotify::fromArray($data);

        $this->mockFilesystem
            ->expects(self::once())
            ->method('read')
            ->with($data['filename'])
            ->willReturn($contents);

        $this->mockNotifyClient
            ->expects(self::once())
            ->method('prepareUpload')
            ->with($contents)
            ->willReturn($prepareUploadResponse);

        $this->mockNotifyClient
            ->expects(self::once())
            ->method('sendEmail')
            ->with(
                $data['recipientEmail'],
                SendToNotifyHandler::NOTIFY_TEMPLATE_DOWNLOAD_A6_INVOICE,
                $this->getPersonalisationData($data, $prepareUploadResponse),
                $data['uuid']
            )
            ->willReturn($response);

        $this->expectException(UnexpectedValueException::class);

        $this->handler->handle($command);
    }

    /**
     * @throws FileNotFoundException
     */
    public function testRetrieveQueueMessageSendToNotifyEmailFailsWhenNoStatusRetrieved(): void
    {
        $data = $this->getData('email', 'letter', null, null);

        $contents = "pdf content";

        $prepareUploadResponse = [
            'file' => 'cGRmIGNvbnRlbnQ=',
            'is_csv' => false
        ];

        $response = [
            "id" => "740e5834-3a29-46b4-9a6f-16142fde533a",
            "reference" => $data['uuid']
        ];

        $notifyId = "740e5834-3a29-46b4-9a6f-16142fde533a";

        $statusResponse = [
            "id" => $notifyId,
            "reference" => $data['uuid'],
            "content" => [
                "subject" => "Test",
                "body" => "More testing",
                "from_email" => "me@test.com"
            ],
            "uri" => "https://api.notifications.service.gov.uk/v2/notifications/daef7d83-9874-4dd8-ac60-d92646e7aaaa",
            "template" => [
                "id" => '',
                "version" => 1,
                "uri" => "https://api.notificaitons.service.gov.uk/service/your_service_id/templates/740e5834-3a29-46b4-9a6f-16142fde533a"
            ]
        ];

        $command = SendToNotify::fromArray($data);

        $this->mockFilesystem
            ->expects(self::once())
            ->method('read')
            ->with($data['filename'])
            ->willReturn($contents);

        $this->mockNotifyClient
            ->expects(self::once())
            ->method('prepareUpload')
            ->with($contents)
            ->willReturn($prepareUploadResponse);

        $this->mockNotifyClient
            ->expects(self::once())
            ->method('sendEmail')
            ->with(
                $data['recipientEmail'],
                null,
                $this->getPersonalisationData($data, $prepareUploadResponse),
                $data['uuid']
            )
            ->willReturn($response);

        $this->mockNotifyClient
            ->expects(self::once())
            ->method('getNotification')
            ->with($response['id'])
            ->willReturn($statusResponse);

        $this->expectException(UnexpectedValueException::class);

        $this->handler->handle($command);
    }

    #[ArrayShape([
        'recipient_name' => "mixed",
        'client_first_name' => "mixed",
        'client_surname' => "mixed",
        'link_to_file' => "array",
        'pending_or_due_report_type' => "mixed",
        'case_number' => "mixed"
    ])] public function getPersonalisationData(array $data, array $prepareUploadResponse): array
    {
        return [
            'recipient_name' => $data['recipientName'],
            'client_first_name' => $data['clientFirstName'],
            'client_surname' => $data['clientSurname'],
            'link_to_file' => $prepareUploadResponse,
            'pending_or_due_report_type' => $data['pendingOrDueReportType'],
            'case_number' => $data['caseNumber'],
        ];
    }

    private function getData(string $sendByMethod, string $sendByDocType, ?string $letterType, ?string $replyToType): array
    {
        return [
            'id' => '123',
            'uuid' => 'asd-456',
            'filename' => 'filename.pdf',
            'documentId' => '456',
            'recipientEmail' => 'test@test.com',
            'recipientName' => 'Test Test',
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

    public function fileSystemWillReturn($filename, string $contents): void
    {
        $this->mockFilesystem
            ->expects(self::once())
            ->method('read')
            ->with($filename)
            ->willReturn($contents);
    }

    /**
     * @param array $response
     * @param array $statusResponse
     * @param SendToNotify $command
     * @param array $payload
     * @return void
     * @throws FileNotFoundException
     */
    public function notifyClientAndAssertions(
        array $response,
        array $statusResponse,
        SendToNotify $command,
        array $payload
    ): void {
        $this->mockNotifyClient
            ->expects(self::once())
            ->method('getNotification')
            ->with($response['id'])
            ->willReturn($statusResponse);

        $this->mockNotifyClient
            ->expects(self::once())
            ->method('listNotifications')
            ->with(['reference' => $response['reference']])
            ->willReturn([]);

        $command = $this->handler->handle($command);

        self::assertEquals($payload['documentId'], $command->getDocumentId());
        self::assertEquals($payload['notifyStatus'], $command->getNotifyStatus());
        self::assertEquals($payload['notifySendId'], $command->getNotifyId());
    }

    /**
     * @throws FileNotFoundException
     */
    public function setupForInvoiceAndLettersWithAssertions(array $data, string $letterTemplate): void
    {
        $contents = "pdf content";

        $prepareUploadResponse = [
            'file' => 'cGRmIGNvbnRlbnQ=',
            'is_csv' => false
        ];

        $response = [
            "id" => "740e5834-3a29-46b4-9a6f-16142fde533a",
            "reference" => $data['uuid']
        ];

        $notifyId = "740e5834-3a29-46b4-9a6f-16142fde533a";

        $notifyStatus = "sending";

        $statusResponse = [
            "id" => $notifyId,
            "reference" => $data['uuid'],
            "status" => $notifyStatus,
            "content" => [
                "subject" => "Test",
                "body" => "More testing",
                "from_email" => "me@test.com"
            ],
            "uri" => "https://api.notifications.service.gov.uk/v2/notifications/daef7d83-9874-4dd8-ac60-d92646e7aaaa",
            "template" => [
                "id" => $letterTemplate,
                "version" => 1,
                "uri" => "https://api.notificaitons.service.gov.uk/service/your_service_id/templates/740e5834-3a29-46b4-9a6f-16142fde533a"
            ]
        ];

        $payload = [
            "documentId" => $data['documentId'],
            "notifySendId" => $notifyId,
            "notifyStatus" => $notifyStatus,
        ];

        $command = SendToNotify::fromArray($data);

        $this->mockFilesystem
            ->expects(self::once())
            ->method('read')
            ->with($data['filename'])
            ->willReturn($contents);

        $this->mockNotifyClient
            ->expects(self::once())
            ->method('prepareUpload')
            ->with($contents)
            ->willReturn($prepareUploadResponse);

        $this->mockNotifyClient
            ->expects(self::once())
            ->method('sendEmail')
            ->with(
                $data['recipientEmail'],
                $letterTemplate,
                $this->getPersonalisationData($data, $prepareUploadResponse),
                $data['uuid']
            )
            ->willReturn($response);

        $this->notifyClientAndAssertions($response, $statusResponse, $command, $payload);
    }
}
