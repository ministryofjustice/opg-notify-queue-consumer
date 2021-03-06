<?php

declare(strict_types=1);

namespace NotifyQueueConsumerTest\Unit\Command\Handler;

use Alphagov\Notifications\Exception as NotifyException;
use Exception;
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
    /**
     * @var Filesystem|MockObject
     */
    private $mockFilesystem;

    /**
     * @var Client|MockObject
     */
    private $mockNotifyClient;

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
    public function testRetrieveQueueMessageSendToNotifyAndReturnCommandSuccess(): void
    {
        $data = [
            'id' => '123',
            'uuid' => 'asd-456',
            'filename' => 'document.pdf',
            'documentId' => '456',
        ];
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
     * @param Exception $notifyException
     * @throws FileNotFoundException
     * @dataProvider notifyExceptionProvider
     */
    public function testSendToNotifyBubblesUpApiExceptionFailure(Exception $notifyException): void
    {
        $data = [
            'id' => '123',
            'uuid' => 'asd-456',
            'filename' => 'document.pdf',
            'documentId' => '456',
        ];
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
        $data = [
            'id' => '123',
            'uuid' => 'asd-456',
            'filename' => 'document.pdf',
            'documentId' => '456',
        ];
        $response = [
            "id" => "740e5834-3a29-46b4-9a6f-16142fde533a",
            "reference" => $data['uuid'],
            "postage" => "first",
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
        $data = [
            'id' => "123",
            'uuid' => 'asd-456',
            'filename' => 'document.pdf',
            'documentId' => 456
        ];

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
        $data = [
            'id' => '123',
            'uuid' => 'asd-456',
            'filename' => 'document.pdf',
            'documentId' => 456
        ];

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
        $data = [
            'id' => '123',
            'uuid' => 'asd-456',
            'filename' => 'document.pdf',
            'documentId' => 456
        ];

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
}
