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
        $pdf = $command->getFilename();

        $contents = $this->filesystem->read($pdf);

        if ($contents === false) {
            throw new UnexpectedValueException("Cannot read PDF");
        }

        // TODO make sure duplicate references are ignored
        $response = $this->notifyClient->sendPrecompiledLetter(
            $command->getUuid(),
            $contents
        );

        if (empty($response['id'])) {
            throw new UnexpectedValueException("No Notify id returned");
        }

        /*
         * The response received from notify is in the following format
         * {
         *     "id": "740e5834-3a29-46b4-9a6f-16142fde533a", //the notify id
         *     "reference": "your-letter-reference", // the uuid for the correspondence
         *     "postage": "postage-you-have-set-or-None" // the type of postage you selected
         * }
         *
         * Once we have that notify id, we can check for the status of our correspondence by using the getNotification
         * method on the notify client which takes in the notify id as an argument. The response provides a 200 and a
         * raft of data which may be useful but we are only concerned with the status
         */
        $statusQuery = $this->notifyClient->getNotification($response['id']);

        if (empty($statusQuery['status'])) {
            throw new UnexpectedValueException(sprintf("No Notify status found for the ID: %s", $response['id']));
        }

        $payload = [
            'documentId' => $command->getDocumentId(),
            'notifySendId' => $response['id'],
            'notifyStatus' => $this->notifyStatusMapper->toSirius($statusQuery['status']),
        ];

        $guzzleResponse = $this->guzzleClient->request(
            'PUT',
            '/api/public/v1/correspondence/update-send-status',
            ['json' => $payload]
        );

        // handle api response
    }
}
