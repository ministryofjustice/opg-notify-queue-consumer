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

        $guzzleResponse = $this->guzzleClient->put('/api/public/v1/correspondence/update-send-status');


        // TODO make sure it handles unique but duplicate messages

        // on success update the api

        // on failure throw an exception
    }
}
