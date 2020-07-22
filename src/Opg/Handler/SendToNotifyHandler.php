<?php

declare(strict_types=1);

namespace Opg\Handler;

use Opg\Command\SendToNotify;

class SendToNotifyHandler
{
    public function __construct()
    {
        // Inject FlySystem, Notify Client, Guzzle Client here...
    }

    public function handle(SendToNotify $command): void
    {
        $pdf = $command->getFilename();

        // fetch pdf from filesystem

        // send to notify

            // TODO make sure it handles unique but duplicate messages

            // on success update the api

        // on failure throw an exception
    }
}
