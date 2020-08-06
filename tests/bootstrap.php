<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

$config = [
    'aws' => [
        'region' => "eu-west-1",
        's3' => [
            'endpoint' => "http://localstack:4572",
            'version' => 'latest',
            'use_path_style_endpoint' => true,
            'bucket' => 'localbucket',
            'prefix' => '/',
            'options' => [
                'ServerSideEncryption' => 'AES256',
            ]
        ],
        'sqs' => [
            'queue_url' => "http://localstack:4576/queue/notify",
            'version' => '2012-11-05', // TODO can this be `latest`?
        ],
    ],
    'consumer' => [
        'sleep_time' => 0
    ],
    'notify' => [
        'api_key' => '8aaa7cd4-b7af-4f49-90be-88d4815ecb72',
    ],
    'sirius' => [
        'update_status_endpoint' => 'not_yet_implemented',
    ],
];
