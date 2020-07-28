<?php

declare(strict_types=1);

return [
    'aws' => [
        'debug' => filter_var(getenv('OPG_CORE_BACK_AWS_DEBUG'), FILTER_VALIDATE_BOOLEAN),
        'region' => getenv('AWS_REGION') ?: "eu-west-1",
        'version' => 'latest',
        's3' => [
            'endpoint' => getenv('AWS_S3_ENDPOINT_URL') ?: null,
            'version' => 'latest',
            'use_path_style_endpoint' => getenv('AWS_S3_USE_PATH_STYLE_ENDPOINT') ?
                boolval(getenv('AWS_S3_USE_PATH_STYLE_ENDPOINT')) : false,
        ],
        'sqs' => [
            'endpoint' => getenv('AWS_SQS_ENDPOINT_URL') ?: "https://sqs.eu-west-1.amazonaws.com",
            'version' => '2012-11-05', // TODO can this be `latest`?
        ],
    ],
    'consumer' => [
        'sleep_time' => getenv('OPG_NOTIFY_QUEUE_CONSUMER_SLEEP_TIME') === false
            ? 1 : (int)getenv('OPG_NOTIFY_QUEUE_CONSUMER_SLEEP_TIME')
    ],
    'notify' => [
        'api_key' => getenv('OPG_NOTIFY_API_KEY') === false ? null : getenv('OPG_NOTIFY_API_KEY')
    ],
    'sirius' => [
        'api_base_uri' => 'https://foo.com/api/',
    ],
];
