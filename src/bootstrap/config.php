<?php

declare(strict_types=1);

use Alphagov\Notifications\Client;

return [
    'aws' => [
        'region' => getenv('AWS_REGION') ?: "eu-west-1",
        's3' => [
            'endpoint' => getenv('AWS_S3_ENDPOINT_URL') ?: null,
            'version' => 'latest',
            'use_path_style_endpoint' => getenv('AWS_S3_USE_PATH_STYLE_ENDPOINT') ?
                boolval(getenv('AWS_S3_USE_PATH_STYLE_ENDPOINT')) : false,
            'bucket' => getenv('AWS_S3_BUCKET') ?: 'localbucket',
            'prefix' => '/',
            'options' => [
                'ServerSideEncryption' => 'AES256',
            ]
        ],
        'sqs' => [
            'queue_url' => getenv('AWS_SQS_QUEUE_URL') ?: null,
            'version' => '2012-11-05', // TODO can this be `latest`?
            'endpoint' => getenv('AWS_SQS_ENDPOINT_URL') ?: null,
            'wait_time' => (int)(getenv('AWS_SQS_QUEUE_WAIT_TIME') ?: 0),
        ],
    ],
    'consumer' => [
        'sleep_time' => getenv('OPG_NOTIFY_QUEUE_CONSUMER_SLEEP_TIME_SECONDS') === false
            ? 1 : (int)getenv('OPG_NOTIFY_QUEUE_CONSUMER_SLEEP_TIME_SECONDS')
    ],
    'notify' => [
        'api_key' => getenv('OPG_NOTIFY_API_KEY') === false ?
            '8aaa7cd4-b7af-4f49-90be-88d4815ecb72' : getenv('OPG_NOTIFY_API_KEY'),
        'base_url' => getenv('OPG_NOTIFY_BASE_URL') === false ?
            Client::BASE_URL_PRODUCTION : getenv('OPG_NOTIFY_BASE_URL'),
    ],
    'sirius' => [
        'update_status_endpoint' => getenv('OPG_SIRIUS_UPDATE_STATUS_ENDPOINT') ?: '/update-status',
    ],
];
