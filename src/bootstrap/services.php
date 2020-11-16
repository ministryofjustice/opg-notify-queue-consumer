<?php

declare(strict_types=1);

use Http\Discovery\Psr17FactoryDiscovery;
use Alphagov\Notifications\Client;
use GuzzleHttp\Client as GuzzleClient;
use Aws\S3\S3Client;
use Aws\Sqs\SqsClient;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\Filesystem;
use NotifyQueueConsumer\Command\Handler\SendToNotifyHandler;
use NotifyQueueConsumer\Command\Handler\UpdateDocumentStatusHandler;
use NotifyQueueConsumer\Queue\Consumer;
use NotifyQueueConsumer\Queue\SqsAdapter;
use NotifyQueueConsumer\Mapper\NotifyStatus;
use NotifyStatusPoller\Authentication\JwtAuthenticator;
use Psr\Log\LoggerInterface;

// Make IDEs not show errors...
/** @var array<mixed> $config */
/** @var LoggerInterface $psrLoggerAdapter */

if (empty($config)) {
    throw new InvalidArgumentException('No config found');
}

$awsS3Client = new S3Client(
    [
        'region' => $config['aws']['region'],
        'version' => $config['aws']['s3']['version'],
        'endpoint' => $config['aws']['s3']['endpoint'],
        'use_path_style_endpoint' => $config['aws']['s3']['use_path_style_endpoint'],
    ]
);

$adapter = new AwsS3Adapter(
    $awsS3Client,
    $config['aws']['s3']['bucket'],
    $config['aws']['s3']['prefix'],
    $config['aws']['s3']['options']
);
$filesystem = new Filesystem($adapter);

$notifyGuzzleClient = new GuzzleClient();

$notifyClient = new Client(
    [
        'apiKey' => $config['notify']['api_key'],
        'httpClient' => $notifyGuzzleClient,
        'baseUrl' => $config['notify']['base_url'],
    ]
);

$guzzleClient = new GuzzleClient([]);

$awsSqsClient = new SqsClient(
    [
        'region' => $config['aws']['region'],
        'version' => $config['aws']['sqs']['version'],
        'endpoint' => $config['aws']['sqs']['endpoint'],
    ]
);

$queue = new SqsAdapter(
    $awsSqsClient,
    $config['aws']['sqs']['queue_url'],
    $config['aws']['sqs']['wait_time']
);

$sendToNotifyHandler = new SendToNotifyHandler(
    $filesystem,
    $notifyClient
);

$jwtAuthenticator = new JwtAuthenticator(
    $config['sirius']['jwt_secret'],
    $config['sirius']['session_data']
);

$updateDocumentStatusHandler = new UpdateDocumentStatusHandler(
    new NotifyStatus(),
    $guzzleClient,
    $jwtAuthenticator,
    $config['sirius']['update_status_endpoint']
);

$consumer = new Consumer(
    $queue,
    $sendToNotifyHandler,
    $updateDocumentStatusHandler,
    $psrLoggerAdapter
);
