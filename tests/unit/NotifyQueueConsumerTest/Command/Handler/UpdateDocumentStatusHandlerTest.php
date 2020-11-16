<?php

declare(strict_types=1);

namespace NotifyQueueConsumerTest\Unit\Command\Handler;

use NotifyStatusPoller\Authentication\JwtAuthenticator;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;
use NotifyQueueConsumer\Command\Model\UpdateDocumentStatus;
use NotifyQueueConsumer\Command\Handler\UpdateDocumentStatusHandler;
use NotifyQueueConsumer\Mapper\NotifyStatus;

class UpdateDocumentStatusHandlerTest extends TestCase
{
    private const ENDPOINT = '/update-status';
    private $mockGuzzleClient;
    private $mockNotifyStatusMapper;
    private JwtAuthenticator $mockAuthenticator;

    private UpdateDocumentStatusHandler $handler;

    public function setUp(): void
    {
        parent::setUp();

        $this->mockNotifyStatusMapper = $this->createMock(NotifyStatus::class);
        $this->mockGuzzleClient = $this->createMock(GuzzleClient::class);
        $this->mockAuthenticator = $this->createMock(JwtAuthenticator::class);
        $this->handler = new UpdateDocumentStatusHandler(
            $this->mockNotifyStatusMapper,
            $this->mockGuzzleClient,
            $this->mockAuthenticator,
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

        self::expectException(UnexpectedValueException::class);
        self::expectExceptionMessage(
            sprintf('Expected status "%s" but received "%s"', 204, $mockResponse->getStatusCode())
        );

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
