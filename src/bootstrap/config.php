<?php

declare(strict_types=1);

return [
    'aws' => [
        'region' => getenv('AWS_REGION') ?: "eu-west-1",
        's3' => [
            'endpoint' => getenv('AWS_S3_ENDPOINT_URL') ?: null,
            'version' => 'latest',
            'use_path_style_endpoint' => getenv('AWS_S3_USE_PATH_STYLE_ENDPOINT') ?
                boolval(getenv('AWS_S3_USE_PATH_STYLE_ENDPOINT')) : false,
            'bucket' => getenv('OPG_CORE_BACK_FILE_PERSISTENCE_S3_BUCKET_NAME') ?: 'localbucket',
            'prefix' => '/',
            'options' => [
                'ServerSideEncryption' => 'AES256',
            ]
        ],
        'sqs' => [
            'endpoint' => getenv('AWS_SQS_ENDPOINT_URL') ?: "https://sqs.eu-west-1.amazonaws.com",
            'version' => '2012-11-05', // TODO can this be `latest`?
        ],
    ],
    'consumer' => [
        'sleep_time' => getenv('OPG_NOTIFY_QUEUE_CONSUMER_SLEEP_TIME_SECONDS') === false
            ? 1 : (int)getenv('OPG_NOTIFY_QUEUE_CONSUMER_SLEEP_TIME_SECONDS')
    ],
    'notify' => [
        'api_key' => getenv('OPG_NOTIFY_API_KEY') === false ? null : getenv('OPG_NOTIFY_API_KEY')
    ],
    'sirius' => [
        'api_base_uri' => 'https://foo.com/api/',
    ],
];
