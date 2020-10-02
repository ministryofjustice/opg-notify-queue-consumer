<?php

declare(strict_types=1);

namespace NotifyQueueConsumerTest\Unit;

use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase {
    public function configDefaults() {
        return [
            'default_of_aws_region' => [
                function ($config) { return $config['aws']['region']; },
                'eu-west-1',
            ],
            'default_of_aws_s3_endpoint' => [
                function ($config) { return $config['aws']['s3']['endpoint']; },
                null,
            ],
            'default_of_aws_s3_version' => [
                function ($config) { return $config['aws']['s3']['version']; },
                'latest',
            ],
            'default_of_aws_s3_use_path_style_endpoint' => [
                function ($config) { return $config['aws']['s3']['use_path_style_endpoint']; },
                false,
            ],
            'default_of_aws_s3_bucket' => [
                function ($config) { return $config['aws']['s3']['bucket']; },
                'localbucket',
            ],
            'default_of_aws_s3_prefix' => [
                function ($config) { return $config['aws']['s3']['prefix']; },
                '/',
            ],
            'default_of_aws_s3_options' => [
                function ($config) { return $config['aws']['s3']['options']; },
                [ 'ServerSideEncryption' => 'AES256',],
            ],
            'default_of_aws_sqs_queue_url' => [
                function ($config) { return $config['aws']['sqs']['queue_url']; },
                null,
            ],
            'default_of_aws_sqs_version' => [
                function ($config) { return $config['aws']['sqs']['version']; },
                '2012-11-05',
            ],
            'default_of_aws_sqs_endpoint' => [
                function ($config) { return $config['aws']['sqs']['endpoint']; },
                null,
            ],
            'default_of_aws_sqs_wait_time' => [
                function ($config) { return $config['aws']['sqs']['wait_time']; },
                0,
            ],
            'default_of_consumer_sleep_time' => [
                function ($config) { return $config['consumer']['sleep_time']; },
                1,
            ],
            'default_of_notify_api_key' => [
                function ($config) { return $config['notify']['api_key']; },
                '8aaa7cd4-b7af-4f49-90be-88d4815ecb72',
            ],
            'default_of_notify_base_url' => [
                function ($config) { return $config['notify']['base_url']; },
                'https://api.notifications.service.gov.uk',
            ],
            'default_of_sirius_update_status_endpoint' => [
                function ($config) { return $config['sirius']['update_status_endpoint']; },
                '/update-status',
            ],
        ];
    }

    /**
     * @dataProvider configDefaults
     * @param callable $configArrayAccess
     * @param mixed $expectedDefault
     */
    public function test_config_defaults(callable $configArrayAccess, $expectedDefault) {
        $config = include(__DIR__ . '/../../../src/bootstrap/config.php');

        self::assertEquals($expectedDefault, $configArrayAccess($config));
    }

    public function configValuesFromEnvironmentVariables() {
        return [
            'set_aws_region' => [
                function ($config) { return $config['aws']['region']; },
                'AWS_REGION',
                'test-region',
                'test-region',
            ],
            'set_aws_s3_endpoint' => [
                function ($config) { return $config['aws']['s3']['endpoint']; },
                'AWS_S3_ENDPOINT_URL',
                'test-endpoint',
                'test-endpoint',
            ],
            'set_aws_s3_use_path_style_endpoint_to_true' => [
                function ($config) { return $config['aws']['s3']['use_path_style_endpoint']; },
                'AWS_S3_USE_PATH_STYLE_ENDPOINT',
                'true',
                true
            ],
            'set_aws_s3_use_path_style_endpoint_to_false' => [
                function ($config) { return $config['aws']['s3']['use_path_style_endpoint']; },
                'AWS_S3_USE_PATH_STYLE_ENDPOINT',
                'false',
                false
            ],
            'set_aws_s3_bucket' => [
                function ($config) { return $config['aws']['s3']['bucket']; },
                'AWS_S3_BUCKET',
                'test-bucket',
                'test-bucket',
            ],
            'set_aws_sqs_queue_url' => [
                function ($config) { return $config['aws']['sqs']['queue_url']; },
                'AWS_SQS_QUEUE_URL',
                'test-queue-url',
                'test-queue-url',
            ],
            'set_aws_sqs_endpoint' => [
                function ($config) { return $config['aws']['sqs']['endpoint']; },
                'AWS_SQS_ENDPOINT_URL',
                'test-endpoint-url',
                'test-endpoint-url',
            ],
            'set_aws_sqs_wait_time' => [
                function ($config) { return $config['aws']['sqs']['wait_time']; },
                'AWS_SQS_QUEUE_WAIT_TIME',
                '10',
                10,
            ],
            'set_consumer_sleep_time' => [
                function ($config) { return $config['consumer']['sleep_time']; },
                'OPG_NOTIFY_QUEUE_CONSUMER_SLEEP_TIME_SECONDS',
                '20',
                20,
            ],
            'set_notify_api_key' => [
                function ($config) { return $config['notify']['api_key']; },
                'OPG_NOTIFY_API_KEY',
                'test-key',
                'test-key',
            ],
            'set_notify_base_url' => [
                function ($config) { return $config['notify']['base_url']; },
                'OPG_NOTIFY_BASE_URL',
                'test-base-url',
                'test-base-url',
            ],
            'set_sirius_update_status_endpoint' => [
                function ($config) { return $config['sirius']['update_status_endpoint']; },
                'OPG_SIRIUS_UPDATE_STATUS_ENDPOINT',
                'test-status-endpoint',
                'test-status-endpoint',
            ],
        ];
    }

    /**
     * @dataProvider configValuesFromEnvironmentVariables
     * @param callable $configArrayAccess
     * @param string $environmentVariableKey
     * @param string $envionmentVaraiableValue
     * @param mixed $expectedConfigValue
     */
    public function test_config_parsing(callable $configArrayAccess, string $environmentVariableKey, string $envionmentVaraiableValue, $expectedConfigValue) {
        putenv($environmentVariableKey . '=' . $envionmentVaraiableValue); // 'AWS_S3_USE_PATH_STYLE_ENDPOINT=false');

        $config = include(__DIR__ . '/../../../src/bootstrap/config.php');

        self::assertEquals($expectedConfigValue, $configArrayAccess($config));
    }
}
