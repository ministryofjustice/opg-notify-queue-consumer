<?php

declare(strict_types=1);

namespace Opg\Handler;

use Alphagov\Notifications\Client;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\Filesystem;
use Opg\Command\SendToNotify;
use Opg\Mapper\NotifyStatus;
use UnexpectedValueException;

class SendToNotifyHandler
{
    private Filesystem $filesystem;
    private Client $notifyClient;
    private GuzzleClient $guzzleClient;
    private NotifyStatus $notifyStatusMapper;

    public function __construct(
        Filesystem $filesystem,
        Client $notifyClient,
        NotifyStatus $notifyStatusMapper,
        GuzzleClient $guzzleClient
    ) {
        $this->filesystem = $filesystem;
        $this->notifyClient = $notifyClient;
        $this->notifyStatusMapper = $notifyStatusMapper;
        $this->guzzleClient = $guzzleClient;
    }

    /**
     * @param SendToNotify $command
     * @throws FileNotFoundException
     * @throws GuzzleException
     */
    public function handle(SendToNotify $command): void
    {
        // 1. Fetch PDF for queued item
        $pdf = $command->getFilename();
        $contents = $this->filesystem->read($pdf);

        if ($contents === false) {
            throw new UnexpectedValueException("Cannot read PDF");
        }

        // 2. Send to notify
        // TODO make sure duplicate references are ignored
        $notifySendResponse = $this->notifyClient->sendPrecompiledLetter(
            $command->getUuid(),
            $contents
        );

        if (empty($notifySendResponse['id'])) {
            throw new UnexpectedValueException("No Notify id returned");
        }

        $notifyStatusResponse = $this->notifyClient->getNotification($notifySendResponse['id']);

        if (empty($notifyStatusResponse['status'])) {
            throw new UnexpectedValueException(
                sprintf("No Notify status found for the ID: %s", $notifySendResponse['id'])
            );
        }

        // 3. Update status on Sirius
        $this->updateSirius($command->getDocumentId(), $notifySendResponse['id'], $notifyStatusResponse['status']);
    }

    /**
     * @param int    $documentId
     * @param string $notifyId
     * @param string $notifyStatus
     * @throws GuzzleException
     */
    private function updateSirius(int $documentId, string $notifyId, string $notifyStatus): void
    {
        $payload = [
            'documentId' => $documentId,
            'notifySendId' => $notifyId,
            'notifyStatus' => $this->notifyStatusMapper->toSirius($notifyStatus),
        ];

        $guzzleResponse = $this->guzzleClient->request(
            'PUT',
            '/api/public/v1/correspondence/update-send-status',
            ['json' => $payload]
        );

        if ($guzzleResponse->getStatusCode() !== 204) {
            throw new UnexpectedValueException(
                sprintf('Expected status "%s" but received "%s"', 204, $guzzleResponse->getStatusCode())
            );
        }
    }
}
