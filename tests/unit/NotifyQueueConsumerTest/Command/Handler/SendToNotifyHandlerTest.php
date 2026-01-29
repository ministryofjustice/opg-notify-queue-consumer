<?php

declare(strict_types=1);

namespace NotifyQueueConsumerTest\Unit\Command\Handler;

use Alphagov\Notifications\Client;
use Alphagov\Notifications\Exception as NotifyException;
use Aws\Command;
use Aws\Exception\AwsException;
use Closure;
use DateTime;
use Exception;
use JetBrains\PhpStorm\ArrayShape;
use League\Flysystem\Filesystem;
use League\Flysystem\UnableToReadFile;
use NotifyQueueConsumer\Command\Handler\SendToNotifyHandler;
use NotifyQueueConsumer\Command\Model\SendToNotify;
use NotifyQueueConsumer\Queue\DuplicateMessageException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use UnexpectedValueException;

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
            ->with($data['uuid'], $contents, 'economy')
            ->willReturn($response);

        $this->notifyClientAndAssertions($response, $statusResponse, $command, $payload);
    }

    #[ArrayShape([
        'A6 Template' => "array",
        'AF1 Template' => "array",
        'AF2 Template' => "array",
        'AF3 Template' => "array",
        'FN14 Template' => "array",
    ])] public static function financeInvoiceLetterData(): array
    {
        return [
            'A6 Template' => ['a6', SendToNotifyHandler::NOTIFY_TEMPLATE_DOWNLOAD_A6_INVOICE, 'FINANCE'],
            'AF1 Template' => ['af1', SendToNotifyHandler::NOTIFY_TEMPLATE_DOWNLOAD_AF_INVOICE, 'FINANCE'],
            'AF2 Template' => ['af2', SendToNotifyHandler::NOTIFY_TEMPLATE_DOWNLOAD_AF_INVOICE, 'FINANCE'],
            'AF3 Template' => ['af3', SendToNotifyHandler::NOTIFY_TEMPLATE_DOWNLOAD_AF_INVOICE, 'FINANCE'],
            'FN14 Template' => ['fn14', SendToNotifyHandler::NOTIFY_TEMPLATE_DOWNLOAD_FN14_LETTER, 'FINANCE']
        ];
    }

    #[DataProvider('financeInvoiceLetterData')]
    public function testRetrieveQueueMessageSendToNotifyEmailInvoiceAndReturnCommandExpected(string $letterType, string $letterTemplate, string $replyToType): void
    {
        $data = $this->getData('email', 'letter', $letterType, $replyToType);

        $this->setupForInvoiceAndLettersWithAssertions($data, $letterTemplate);
    }

    #[ArrayShape([
        'A9 Template' => "array",
        'PASPR Template' => "array",
    ])] public static function emailData(): array
    {
        return [
            'A9 Template' => ['a9', SendToNotifyHandler::NOTIFY_TEMPLATE_DOWNLOAD_A9_LETTER, 'PFA LAY'],
            'PASPR Template' => ['paspr', SendToNotifyHandler::NOTIFY_TEMPLATE_DOWNLOAD_PA_MONTHLY_SPREADSHEET, 'PFA PA']
        ];
    }

    #[DataProvider('emailData')]
    public function testRetrieveQueueMessageSendToNotifyEmailAndReturnCommandExpected(string $letterType, string $letterTemplate, string $replyToType): void
    {
        $data = $this->getData('email', 'letter', $letterType, $replyToType);

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
    ])] public static function annualReportLetterData(): array
    {
        return [
            'BS1 Template' => ['bs1', SendToNotifyHandler::NOTIFY_TEMPLATE_DOWNLOAD_BS1_LETTER, 'HW'],
            'BS2 Template' => ['bs2', SendToNotifyHandler::NOTIFY_TEMPLATE_DOWNLOAD_BS2_LETTER, 'HW'],
            'RD1 Template' => ['rd1', SendToNotifyHandler::NOTIFY_TEMPLATE_DOWNLOAD_RD1_LETTER, 'PFA LAY'],
            'RD2 Template' => ['rd2', SendToNotifyHandler::NOTIFY_TEMPLATE_DOWNLOAD_RD2_LETTER, 'PFA PRO'],
            'RI2 Template' => ['ri2', SendToNotifyHandler::NOTIFY_TEMPLATE_DOWNLOAD_RI2_LETTER, 'PFA PA'],
            'RI3 Template' => ['ri3', SendToNotifyHandler::NOTIFY_TEMPLATE_DOWNLOAD_RI3_LETTER, 'PFA'],
            'RR1 Template' => ['rr1', SendToNotifyHandler::NOTIFY_TEMPLATE_DOWNLOAD_RR1_LETTER, 'HW'],
            'RR2 Template' => ['rr2', SendToNotifyHandler::NOTIFY_TEMPLATE_DOWNLOAD_RR2_LETTER, 'HW'],
            'RR3 Template' => ['rr3', SendToNotifyHandler::NOTIFY_TEMPLATE_DOWNLOAD_RR3_LETTER, 'HW'],
        ];
    }

    #[DataProvider('annualReportLetterData')]
    public function testRetrieveQueueMessageSendToNotifyEmailLetterAndReturnCommandExpected(string $letterType, string $letterTemplate, ?string $replyToType): void
    {
        $data = $this->getData('email', 'letter', $letterType, $replyToType);

        $this->setupForInvoiceAndLettersWithAssertions($data, $letterTemplate);
    }

    #[ArrayShape([
        'FF2 Template' => "array",
        'FF3 Template' => "array",
        'FF4 Template' => "array",
        "PASPR Template" => "array",
    ])] public static function debtChaseLetterData(): array
    {
        return [
            'FF2 Template' => ['ff2', SendToNotifyHandler::NOTIFY_TEMPLATE_DOWNLOAD_FF2_LETTER],
            'FF3 Template' => ['ff3', SendToNotifyHandler::NOTIFY_TEMPLATE_DOWNLOAD_FF3_LETTER],
            'FF4 Template' => ['ff4', SendToNotifyHandler::NOTIFY_TEMPLATE_DOWNLOAD_FF4_LETTER],
        ];
    }

    #[DataProvider('debtChaseLetterData')]
    public function testRetrieveQueueMessageSendToNotifyDebtChaseLetterAndReturnCommandExpected(string $letterType, string $letterTemplate): void
    {
        $data = $this->getData('email', 'letter', $letterType, null);

        $this->setupForInvoiceAndLettersWithAssertions($data, $letterTemplate);
    }
    
    /**
     * @param Exception $notifyException
     */
    #[DataProvider('notifyExceptionProvider')]
    public function testSendToNotifyBubblesUpApiExceptionFailure(Closure $notifyExceptionClosure): void
    {
        $mockHttpResponse = $this->createMock(ResponseInterface::class);
        $notifyException = $notifyExceptionClosure($mockHttpResponse);

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

    public static function notifyExceptionProvider(): array
    {
        return [
            [
                function ($mockResponse): Exception {
                    return new NotifyException\ApiException(
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
                        $mockResponse
                    );
                }
            ],
            [
                function ($mockResponse): Exception {
                    return new NotifyException\NotifyException('something went wrong');
                }
            ],
            [
                function ($mockResponse): Exception {
                    return new NotifyException\UnexpectedValueException('something went wrong');
                }
            ],
        ];
    }

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

    public function testEmptyPdfInQueueFailure(): void
    {
        $data = $this->getData('post', 'letter', null, null);

        $command = SendToNotify::fromArray($data);

        $this->mockFilesystem->method('read')->willThrowException(new UnableToReadFile(
            "file does not exist",
            0,
            new AwsException('no permission to access KMS key', new Command('PutObject'))
        ));

        self::expectException(UnexpectedValueException::class);
        self::expectExceptionMessage("Cannot read PDF: file does not exist: no permission to access KMS key");

        $this->handler->handle($command);
    }

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
            ->with($data['uuid'], $contents, 'economy')
            ->willReturn($response);

        self::expectException(UnexpectedValueException::class);
        self::expectExceptionMessage("No Notify id returned");

        $this->handler->handle($command);
    }

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
            ->with($data['uuid'], $contents, 'economy')
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

        $replyToType = match ($command->getReplyToType()) {
            'HW' => SendToNotifyHandler::NOTIFY_EMAIL_HEALTH_AND_WELFARE,
            'PFA LAY' => SendToNotifyHandler::NOTIFY_EMAIL_PFA_LAY,
            'PFA PRO' => SendToNotifyHandler::NOTIFY_EMAIL_PFA_PRO,
            'PFA PA' => SendToNotifyHandler::NOTIFY_EMAIL_PFA_PA,
            'FINANCE' => SendToNotifyHandler::NOTIFY_EMAIL_FINANCE,
            default => null
        };

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
                $data['uuid'],
                $replyToType
            )
            ->willReturn($response);

        $this->expectException(UnexpectedValueException::class);

        $this->handler->handle($command);
    }

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

        $replyToType = match ($command->getReplyToType()) {
            'HW' => SendToNotifyHandler::NOTIFY_EMAIL_HEALTH_AND_WELFARE,
            'PFA LAY' => SendToNotifyHandler::NOTIFY_EMAIL_PFA_LAY,
            'PFA PRO' => SendToNotifyHandler::NOTIFY_EMAIL_PFA_PRO,
            'PFA PA' => SendToNotifyHandler::NOTIFY_EMAIL_PFA_PA,
            'FINANCE' => SendToNotifyHandler::NOTIFY_EMAIL_FINANCE,
            default => null
        };

        $this->mockFilesystem
            ->expects(self::once())
            ->method('read')
            ->with($data['filename'])
            ->willReturn($contents);

        $this->mockNotifyClient
            ->expects(self::once())
            ->method('prepareUpload')
            ->with($contents, false, null, '56 weeks')
            ->willReturn($prepareUploadResponse);

        $this->mockNotifyClient
            ->expects(self::once())
            ->method('sendEmail')
            ->with(
                $data['recipientEmail'],
                null,
                $this->getPersonalisationData($data, $prepareUploadResponse),
                $data['uuid'],
                $replyToType
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
        'case_number' => "mixed",
        'last_month' => "mixed",
    ])] public function getPersonalisationData(array $data, array $prepareUploadResponse): array
    {
        return [
            'recipient_name' => $data['recipientName'],
            'client_first_name' => $data['clientFirstName'],
            'client_surname' => $data['clientSurname'],
            'link_to_file' => $prepareUploadResponse,
            'pending_or_due_report_type' => $data['pendingOrDueReportType'],
            'case_number' => $data['caseNumber'],
            'last_month' => $data['lastMonth'],
        ];
    }

    private function getData(string $sendByMethod, string $sendByDocType, ?string $letterType, ?string $replyToType): array
    {
        $now = new DateTime();
        $previousMonth = $now->modify('first day of previous month');
        $lastMonth = $previousMonth->format('F');

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
            'lastMonth' => $lastMonth,
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

        $replyToType = match ($command->getReplyToType()) {
            'HW' => SendToNotifyHandler::NOTIFY_EMAIL_HEALTH_AND_WELFARE,
            'PFA LAY' => SendToNotifyHandler::NOTIFY_EMAIL_PFA_LAY,
            'PFA PRO' => SendToNotifyHandler::NOTIFY_EMAIL_PFA_PRO,
            'PFA PA' => SendToNotifyHandler::NOTIFY_EMAIL_PFA_PA,
            'FINANCE' => SendToNotifyHandler::NOTIFY_EMAIL_FINANCE,
            default => null
        };

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
                $data['uuid'],
                $replyToType
            )
            ->willReturn($response);

        $this->notifyClientAndAssertions($response, $statusResponse, $command, $payload);
    }
}
