<?php

declare(strict_types=1);

namespace NotifyQueueConsumer\Command\Handler;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use NotifyQueueConsumer\Authentication\JwtAuthenticator;
use NotifyQueueConsumer\Command\Model\UpdateDocumentStatus;
use NotifyQueueConsumer\Mapper\NotifyStatus;
use Psr\Log\LoggerInterface;
use UnexpectedValueException;

class UpdateDocumentStatusHandler
{
    private NotifyStatus $notifyStatusMapper;
    private GuzzleClient $guzzleClient;
    private JwtAuthenticator $jwtAuthenticator;
    private LoggerInterface $logger;
    private string $updateEndpointUrl;

    public function __construct(
        NotifyStatus $notifyStatusMapper,
        GuzzleClient $guzzleClient,
        JwtAuthenticator $jwtAuthenticator,
        LoggerInterface $logger,
        string $updateEndpointUrl
    ) {
        $this->notifyStatusMapper = $notifyStatusMapper;
        $this->guzzleClient = $guzzleClient;
        $this->jwtAuthenticator = $jwtAuthenticator;
        $this->logger = $logger;
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
            'notifySubStatus' => $command->getNotifyStatus(),
            'sendByMethod' => $command->getSendByMethod(),
            'recipientEmailAddress' => $command->getRecipientEmailAddress(),
            'postage' => $command->getPostage(),
        ];

        try {
            $guzzleResponse = $this->guzzleClient->put(
                $this->updateEndpointUrl,
                ['headers' => $this->jwtAuthenticator->createToken(), 'json' => $payload]
            );
        } catch (ClientException $e) {
            $this->logger->info($e->getMessage(), [
                'body' => (string) $e->getResponse()->getBody()
            ]);
            throw $e;
        }

        if ($guzzleResponse->getStatusCode() !== 204) {
            throw new UnexpectedValueException(
                sprintf('Expected status "%s" but received "%s"', 204, $guzzleResponse->getStatusCode())
            );
        }
    }
}
