<?php

declare(strict_types=1);

use Http\Client\Curl\Client as CurlClient;
use Http\Discovery\Psr17FactoryDiscovery;
use Alphagov\Notifications\Client;
use GuzzleHttp\Client as GuzzleClient;
use Aws\S3\S3Client;
use Aws\Sqs\SqsClient;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\Filesystem;
use Opg\Handler\SendToNotifyHandler;
use Opg\Logging\Context;
use Opg\Queue\Consumer;
use Opg\Queue\SqsAdapter;
use Opg\Mapper\NotifyStatus;

require_once __DIR__ . '/../vendor/autoload.php';
$config = include __DIR__ . '/../src/bootstrap/config.php';
require_once __DIR__ . '/../src/bootstrap/logging.php';

try {
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // Flysystem ///////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    $client = new S3Client([
        'region' => $config['aws']['region'],
        'version' => $config['aws']['s3']['version'],
    ]);
    $adapter = new AwsS3Adapter($client, 'your-bucket-name', 'optional/path/prefix');
    $filesystem = new Filesystem($adapter);

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // Notify //////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    $notifyClient = new Client([
        'apiKey' => $config['notify']['api_key'],
        'httpClient' => new CurlClient(
            Psr17FactoryDiscovery::findResponseFactory(),
            Psr17FactoryDiscovery::findStreamFactory()
        ),
    ]);

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // Guzzle //////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    $guzzleClient = new GuzzleClient($config['sirius']['api_base_uri']);

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // Queue ///////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    $awsSqsClient = new SqsClient([
        'region' => $config['aws']['region'],
        'version' => $config['aws']['sqs']['version']
    ]);
    $queue = new SqsAdapter($awsSqsClient, $config['aws']['sqs']['endpoint']);

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // Message Handler /////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    $messageHandler = new SendToNotifyHandler(
        $filesystem,
        $notifyClient,
        new NotifyStatus(),
        $guzzleClient
    );

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // Consumer ////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    $consumer = new Consumer($queue, $messageHandler, $psrLoggerAdapter);

    while ($doRunLoop) {
        $consumer->run();

        sleep($config['consumer']['sleep_time']);
    }
} catch (Throwable $e) {
    exception_handler($e);
}

$psrLoggerAdapter->info('Finished', ['context' => Context::NOTIFY_CONSUMER]);

exit(0);
