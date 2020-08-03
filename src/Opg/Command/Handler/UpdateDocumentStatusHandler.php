<?php

declare(strict_types=1);

namespace Opg\Command\Handler;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;
use Opg\Command\Model\UpdateDocumentStatus;
use Opg\Mapper\NotifyStatus;
use UnexpectedValueException;

class UpdateDocumentStatusHandler
{
    private NotifyStatus $notifyStatusMapper;
    private GuzzleClient $guzzleClient;
    private string $updateEndpointUrl;

    public function __construct(
        NotifyStatus $notifyStatusMapper,
        GuzzleClient $guzzleClient,
        string $updateEndpointUrl
    ) {
        $this->notifyStatusMapper = $notifyStatusMapper;
        $this->guzzleClient = $guzzleClient;
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
            ['json' => $payload]
        );

        if ($guzzleResponse->getStatusCode() !== 204) {
            throw new UnexpectedValueException(
                sprintf('Expected status "%s" but received "%s"', 204, $guzzleResponse->getStatusCode())
            );
        }
    }
}
