<?php

declare(strict_types=1);

namespace OpgTest\Handler;

use UnexpectedValueException;
use Alphagov\Notifications\Client;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\Filesystem;
use Opg\Command\SendToNotify;
use Opg\Handler\SendToNotifyHandler;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

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

    /**
     * @var GuzzleClient|MockObject
     */
    private $mockGuzzleClient;

    private SendToNotifyHandler $handler;


    public function setUp(): void
    {
        parent::setUp();
        $this->mockFilesystem = $this->createMock(Filesystem::class);
        $this->mockNotifyClient = $this->createMock(Client::class);
        $this->mockGuzzleClient = $this->createMock(GuzzleClient::class);
        $this->handler = new SendToNotifyHandler(
            $this->mockFilesystem,
            $this->mockNotifyClient,
            $this->mockGuzzleClient
        );
    }

    /**
     * @throws FileNotFoundException
     * @throws GuzzleException
     */
    public function testSendToNotifySuccess(): void
    {
        $data = [
            'id' => '123',
            'uuid' => 'asd-456',
            'filename' => 'document.pdf',
            'documentId' => 456
        ];

        $contents = "abcdef";

        $response = [
            "id" => "740e5834-3a29-46b4-9a6f-16142fde533a",
            "reference" => "unique_ref123",
            "postage" => "first"
        ];

        $statusQuery = [
              "id" => "740e5834-3a29-46b4-9a6f-16142fde533a",
              "reference" => "unique_ref123",
              "line_1" => "742 Evergreen Terrace ",
              "line_2" => "Springfield",
              "postcode" => "S1M 2SO",
              "type" => "letter",
              "status" => "sending",
              "body" => "",
              "created_at" => "",
              "created_by_name" => "",
              "sent_at" => "",
              "completed_at" => ""
        ];

        $payload = [
            456,
            "740e5834-3a29-46b4-9a6f-16142fde533a",
            "sending"
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
            ->willReturn($statusQuery);

        $this->mockGuzzleClient
            ->expects(self::once())
            ->method('request')
            ->with('PUT', '/api/public/v1/correspondence/update-send-status', ['json' => $payload]);

        $this->handler->handle($command);
    }

    /**
     * @throws FileNotFoundException
     * @throws GuzzleException
     */
    public function testEmptyPdfFailure(): void
    {
        $data = [
            'id' => "123",
            'uuid' => 'asd-456',
            'filename' => 'document.pdf',
            'documentId' => 456
        ];

        $command = SendToNotify::fromArray($data);

        $this->mockFilesystem->method('read')->willReturn(false);

        self::expectException(\UnexpectedValueException::class);
        self::expectExceptionMessage("Cannot read PDF");

        $this->handler->handle($command);
    }

    /**
     * @throws GuzzleException
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
     * @throws GuzzleException
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
