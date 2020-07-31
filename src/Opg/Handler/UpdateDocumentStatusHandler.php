<?php

declare(strict_types=1);

namespace Opg\Handler;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;
use Opg\Command\UpdateDocumentStatus;
use Opg\Mapper\NotifyStatus;
use UnexpectedValueException;

class UpdateDocumentStatusHandler
{
    private NotifyStatus $notifyStatusMapper;
    private GuzzleClient $guzzleClient;

    public function __construct(NotifyStatus $notifyStatusMapper, GuzzleClient $guzzleClient)
    {
        $this->notifyStatusMapper = $notifyStatusMapper;
        $this->guzzleClient = $guzzleClient;
    }

    /**
     * @param UpdateDocumentStatus $command
     * @throws GuzzleException
     */
    public function handle(UpdateDocumentStatus $command)
    {
        $payload = [
            'documentId' => $command->getDocumentId(),
            'notifySendId' => $command->getNotifyId(),
            'notifyStatus' => $this->notifyStatusMapper->toSirius($command->getNotifyStatus()),
        ];

        $guzzleResponse = $this->guzzleClient->put(
            'http://api/api/public/v1/correspondence/update-send-status',
            ['json' => $payload]
        );

        if ($guzzleResponse->getStatusCode() !== 204) {
            throw new UnexpectedValueException(
                sprintf('Expected status "%s" but received "%s"', 204, $guzzleResponse->getStatusCode())
            );
        }
    }
}
