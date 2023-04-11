<?php

declare(strict_types=1);

use Alphagov\Notifications\Client;
use Aws\S3\S3Client;
use Aws\Sqs\SqsClient;
use GuzzleHttp\Client as GuzzleClient;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use League\Flysystem\Filesystem;
use NotifyQueueConsumer\Authentication\JwtAuthenticator;
use NotifyQueueConsumer\Command\Handler\SendToNotifyHandler;
use NotifyQueueConsumer\Command\Handler\UpdateDocumentStatusHandler;
use NotifyQueueConsumer\Mapper\NotifyStatus;
use NotifyQueueConsumer\Queue\Consumer;
use NotifyQueueConsumer\Queue\SqsAdapter;
use Psr\Log\LoggerInterface;

// Make IDEs not show errors...
/** @var array<mixed> $config */
/** @var LoggerInterface $psrLoggerAdapter */

if (empty($config)) {
    throw new InvalidArgumentException('No config found');
}

$s3ClientConfig = [
    'region' => $config['aws']['region'],
    'version' => $config['aws']['s3']['version'],
];

if (isset($config['aws']['s3']['use_path_style_endpoint']) && $config['aws']['s3']['use_path_style_endpoint']) {
    $s3ClientConfig['endpoint'] = $config['aws']['s3']['endpoint'];
    $s3ClientConfig['use_path_style_endpoint'] = $config['aws']['s3']['use_path_style_endpoint'];
}

try {
    $awsS3Client = new S3Client($s3ClientConfig);
} catch (Throwable $ex) {
    $psrLoggerAdapter->critical('Could not create S3 client. S3 config: ' . print_r($s3ClientConfig, true));
    throw $ex;
}

$adapter = new AwsS3V3Adapter(
    $awsS3Client,
    $config['aws']['s3']['bucket'],
    $config['aws']['s3']['prefix'],
    options: $config['aws']['s3']['options']
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
    $notifyClient,
    $psrLoggerAdapter
);

$jwtAuthenticator = new JwtAuthenticator(
    $config['sirius']['jwt_secret'],
    $config['sirius']['api_user_email']
);

$updateDocumentStatusHandler = new UpdateDocumentStatusHandler(
    new NotifyStatus(),
    $guzzleClient,
    $jwtAuthenticator,
    $psrLoggerAdapter,
    $config['sirius']['update_status_endpoint']
);

$consumer = new Consumer(
    $queue,
    $sendToNotifyHandler,
    $updateDocumentStatusHandler,
    $psrLoggerAdapter,
    fn() => sleep($config['consumer']['update_retry_time']),
);

// Promote globals for use in PHPUnit functional tests
if ($GLOBALS['exportGlobalsInSuperGlobal']) {
    $GLOBALS['awsS3Client'] = $awsS3Client;
    $GLOBALS['awsSqsClient'] = $awsSqsClient;
    $GLOBALS['filesystem'] = $filesystem;
    $GLOBALS['consumer'] = $consumer;
    $GLOBALS['sendToNotifyHandler'] = $sendToNotifyHandler;
    $GLOBALS['updateDocumentStatusHandler'] = $updateDocumentStatusHandler;
}
