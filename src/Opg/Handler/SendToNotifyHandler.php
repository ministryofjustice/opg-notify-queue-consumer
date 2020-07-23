<?php

declare(strict_types=1);

namespace Opg\Handler;

use Alphagov\Notifications\Client;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\Filesystem;
use Opg\Command\SendToNotify;
use UnexpectedValueException;

class SendToNotifyHandler
{
    private Filesystem $filesystem;
    private Client $client;

    public function __construct(Filesystem $filesystem, Client $client)
    {
        // Inject FlySystem, Notify Client, Guzzle Client here...

        $this->filesystem = $filesystem;
        $this->client = $client;
    }

    /**
     * @param SendToNotify $command
     * @throws FileNotFoundException
     */
    public function handle(SendToNotify $command): void
    {
        $pdf = $command->getFilename();

        $contents = $this->filesystem->read($pdf);

        if ($contents === false) {
            throw new UnexpectedValueException("Cannot read PDF");
        }

        $response = $this->client->sendPrecompiledLetter(
            $command->getUuid(),
            $contents
            );
        // send to notify

        // TODO make sure it handles unique but duplicate messages

        // on success update the api

        // on failure throw an exception
    }
}
