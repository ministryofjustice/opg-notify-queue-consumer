<?php

declare(strict_types=1);

namespace NotifyQueueConsumer\Command\Handler;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;
use NotifyQueueConsumer\Command\Model\UpdateDocumentStatus;
use NotifyQueueConsumer\Mapper\NotifyStatus;
use NotifyStatusPoller\Authentication\JwtAuthenticator;
use UnexpectedValueException;

class UpdateDocumentStatusHandler
{
    private NotifyStatus $notifyStatusMapper;
    private GuzzleClient $guzzleClient;
    private JwtAuthenticator $jwtAuthenticator;
    private string $updateEndpointUrl;

    public function __construct(
        NotifyStatus $notifyStatusMapper,
        GuzzleClient $guzzleClient,
        JwtAuthenticator $jwtAuthenticator,
        string $updateEndpointUrl
    ) {
        $this->notifyStatusMapper = $notifyStatusMapper;
        $this->guzzleClient = $guzzleClient;
        $this->jwtAuthenticator = $jwtAuthenticator;
        $this->updateEndpointUrl = $updateEndpointUrl;
    }

    /**
     * @param UpdateDocumentStatus $command
     * @throws GuzzleException
     */
    public function handle(UpdateDocumentStatus $command): void
    {
        $payload = [
            'documentId' => $command->getDocumentId(),
            'notifySendId' => $command->getNotifyId(),
            'notifyStatus' => $this->notifyStatusMapper->toSirius($command->getNotifyStatus()),
        ];

        $guzzleResponse = $this->guzzleClient->put(
            $this->updateEndpointUrl,
            ['headers' => $this->jwtAuthenticator->createToken(), 'json' => $payload]
        );

        if ($guzzleResponse->getStatusCode() !== 204) {
            throw new UnexpectedValueException(
                sprintf('Expected status "%s" but received "%s"', 204, $guzzleResponse->getStatusCode())
            );
        }
    }
}
