<?php

declare(strict_types=1);

namespace NotifyQueueConsumerTest\Unit\Command\Handler;

use NotifyQueueConsumer\Authentication\JwtAuthenticator;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use NotifyQueueConsumer\Command\Model\UpdateDocumentStatus;
use NotifyQueueConsumer\Command\Handler\UpdateDocumentStatusHandler;
use NotifyQueueConsumer\Mapper\NotifyStatus;
use Psr\Log\LoggerInterface;

class UpdateDocumentStatusHandlerTest extends TestCase
{
    private const ENDPOINT = '/update-status';
    private $mockGuzzleClient;
    private $mockAuthenticator;
    private $mockNotifyStatusMapper;
    private $mockLogger;

    private UpdateDocumentStatusHandler $handler;

    public function setUp(): void
    {
        parent::setUp();

        $this->mockNotifyStatusMapper = $this->createMock(NotifyStatus::class);
        $this->mockGuzzleClient = $this->createMock(GuzzleClient::class);
        $this->mockAuthenticator = $this->createMock(JwtAuthenticator::class);
        $this->mockLogger = $this->createMock(LoggerInterface::class);
        $this->handler = new UpdateDocumentStatusHandler(
            $this->mockNotifyStatusMapper,
            $this->mockGuzzleClient,
            $this->mockAuthenticator,
            $this->mockLogger,
            self::ENDPOINT
        );
    }

    /**
     * @throws GuzzleException
     */
    public function testUpdateStatusSuccess(): void
    {
        $command = $this->createUpdateDocumentStatusCommand();
        $siriusStatus = 'status';
        $payload = [
            'documentId' => $command->getDocumentId(),
            'notifySendId' => $command->getNotifyId(),
            'notifyStatus' => $siriusStatus,
        ];

        $this->mockNotifyStatusMapper
            ->expects(self::once())
            ->method('toSirius')
            ->with($command->getNotifyStatus())
            ->willReturn($siriusStatus);


        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->expects(self::once())->method('getStatusCode')->willReturn(204);

        $this->mockGuzzleClient
            ->expects(self::once())
            ->method('put')
            ->with(self::ENDPOINT, ['headers' => $this->mockAuthenticator->createToken(), 'json' => $payload])
            ->willReturn($mockResponse);

        $this->handler->handle($command);
    }

    /**
     * @throws GuzzleException
     */
    public function testInvalidResponseStatusCodeFailure(): void
    {
        $command = $this->createUpdateDocumentStatusCommand();
        $siriusStatus = 'status';
        $payload = [
            'documentId' => $command->getDocumentId(),
            'notifySendId' => $command->getNotifyId(),
            'notifyStatus' => $siriusStatus,
        ];

        $this->mockNotifyStatusMapper
            ->expects(self::once())
            ->method('toSirius')
            ->with($command->getNotifyStatus())
            ->willReturn($siriusStatus);


        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getStatusCode')->willReturn(200);

        $this->mockGuzzleClient
            ->expects(self::once())
            ->method('put')
            ->with(self::ENDPOINT, ['headers' => $this->mockAuthenticator->createToken(), 'json' => $payload])
            ->willReturn($mockResponse);

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage(
            sprintf('Expected status "%s" but received "%s"', 204, $mockResponse->getStatusCode())
        );

        $this->handler->handle($command);
    }

    /**
     * @throws GuzzleException
     */
    public function testErrorResponseStatusCodeFailure(): void
    {
        $command = $this->createUpdateDocumentStatusCommand();
        $siriusStatus = 'status';
        $payload = [
            'documentId' => $command->getDocumentId(),
            'notifySendId' => $command->getNotifyId(),
            'notifyStatus' => $siriusStatus,
        ];

        $this->mockNotifyStatusMapper
            ->expects(self::once())
            ->method('toSirius')
            ->with($command->getNotifyStatus())
            ->willReturn($siriusStatus);

        $this->mockGuzzleClient
            ->expects(self::once())
            ->method('put')
            ->with(self::ENDPOINT, ['headers' => $this->mockAuthenticator->createToken(), 'json' => $payload])
            ->willThrowException(new ClientException('some message', new Request('put', '/'), new Response(404, [], 'this is the problem')));

        $this->mockLogger
            ->expects(self::once())
            ->method('info')
            ->with('some message', ['body' => 'this is the problem']);
        
        $this->expectException(ClientException::class);

        $this->handler->handle($command);
    }

    private function createUpdateDocumentStatusCommand(): UpdateDocumentStatus
    {
        return UpdateDocumentStatus::fromArray([
            'notifyId' => '1',
            'notifyStatus' => 'accepted',
            'documentId' => '4545',
        ]);
    }
}
