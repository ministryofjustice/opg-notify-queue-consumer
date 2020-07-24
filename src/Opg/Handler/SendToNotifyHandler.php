<?php

declare(strict_types=1);

namespace Opg\Handler;

use Alphagov\Notifications\Client;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\Filesystem;
use Opg\Command\SendToNotify;
use UnexpectedValueException;
use InvalidArgumentException;

use function PHPUnit\Framework\isNull;

class SendToNotifyHandler
{
    private Filesystem $filesystem;
    private Client $notifyClient;
    private GuzzleClient $guzzleClient;

    public function __construct(Filesystem $filesystem, Client $notifyClient, GuzzleClient $guzzleClient)
    {
        // Inject FlySystem, Notify Client, Guzzle Client here...

        $this->filesystem = $filesystem;
        $this->notifyClient = $notifyClient;
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

        $response = $this->notifyClient->sendPrecompiledLetter(
            $command->getUuid(),
            $contents
        );

        /*
         * The response received from notify is in the following format
         * {
              "id": "740e5834-3a29-46b4-9a6f-16142fde533a", //the notify id
              "reference": "your-letter-reference", // the uuid for the correspondence
              "postage": "postage-you-have-set-or-None" // the type of postage you selected
            }
         *
         * */

        /*
         * Once we have that notify id, we can check for the status of our correspondence
         * by using the getNotification method on the notify client which takes in the notify
         * id as an argument. The response provides a 200 and a raft of data which may be useful
         * but we are only concerned with the status */
        $statusQuery = $this->notifyClient->getNotification($response['id']);

        //TODO the statusQuery returns Null if the notification cannot be found
//        if (isNull($statusQuery)) {
//            throw new InvalidArgumentException("No notification found for the ID");
//        }

        $correspondenceStatus = $statusQuery['status'];

        /*
         * TODO the status from the notification response states the status is either sending, delivered,
         * permanent-failure, temporary-failure, technical failure - in the original endpoint ticket these were
         * queued for sending, Sent for posting (the only one which should give a notify id), Posted and rejected.
         * Are we meant to map these statuses to statuses from Notify and/or set these depending on the part of the process
         * the correspondence has got to?*/

        $payload = [
            $command->getDocumentId(), //the document id in the database
            $response['id'], //the notify id
            $correspondenceStatus //the status of the correspondence
        ];

        $guzzleResponse = $this->guzzleClient->request(
            'PUT',
            '/api/public/v1/correspondence/update-send-status',
            ['json' => $payload]
        );


        // TODO make sure it handles unique but duplicate messages

        // on success update the api

        // on failure throw an exception
    }
}
