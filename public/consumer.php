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
use Opg\Command\Handler\SendToNotifyHandler;
use Opg\Command\Handler\UpdateDocumentStatusHandler;
use Opg\Logging\Context;
use Opg\Queue\Consumer;
use Opg\Queue\SqsAdapter;
use Opg\Mapper\NotifyStatus;
use Psr\Log\LoggerInterface;

/** @var LoggerInterface $psrLoggerAdapter */
$psrLoggerAdapter = null;
$doRunLoop = false;

require_once __DIR__ . '/../vendor/autoload.php';
$config = include __DIR__ . '/../src/bootstrap/config.php';
require_once __DIR__ . '/../src/bootstrap/logging.php';


// Initialise dependencies before starting the consumer
try {
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // Flysystem ///////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    $awsS3Client = new S3Client([
        'region' => $config['aws']['region'],
        'version' => $config['aws']['s3']['version'],
        'endpoint' => $config['aws']['s3']['endpoint'],
        'use_path_style_endpoint' => $config['aws']['s3']['use_path_style_endpoint'],
    ]);
    $adapter = new AwsS3Adapter(
        $awsS3Client,
        $config['aws']['s3']['bucket'],
        $config['aws']['s3']['prefix'],
        $config['aws']['s3']['options']
    );
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
    $guzzleClient = new GuzzleClient([
        'base_uri' => $config['sirius']['api_base_uri'],
    ]);

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // Queue ///////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    $awsSqsClient = new SqsClient([
        'region' => $config['aws']['region'],
        'version' => $config['aws']['sqs']['version']
    ]);
    $queue = new SqsAdapter($awsSqsClient, $config['aws']['sqs']['queue_url']);

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // Handlers ////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    $sendToNotifyHandler = new SendToNotifyHandler(
        $filesystem,
        $notifyClient
    );

    $updateDocumentStatusHandler = new UpdateDocumentStatusHandler(
        new NotifyStatus(),
        $guzzleClient,
        $config['sirius']['update_status_endpoint']
    );

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // Consumer ////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    $consumer = new Consumer(
        $queue,
        $sendToNotifyHandler,
        $updateDocumentStatusHandler,
        $psrLoggerAdapter
    );

    while ($doRunLoop) {
        $consumer->run();

        sleep($config['consumer']['sleep_time']);
    }
} catch (Throwable $e) {
    exception_handler($e);
}

$psrLoggerAdapter->info('Finished', ['context' => Context::NOTIFY_CONSUMER]);

exit(0);
