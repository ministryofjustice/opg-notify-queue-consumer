<?php

declare(strict_types=1);

namespace Opg\Queue;

use Aws\Sqs\SqsClient;
use Opg\Command\SendToNotify;

class SqsAdapter implements QueueInterface
{
    private SqsClient $client;
    private string $queueUrl;

    public function __construct(SqsClient $client, string $queueUrl)
    {
        $this->client = $client;
        $this->queueUrl = $queueUrl;
    }

    public function next(): ?SendToNotify
    {
        $result = $this->client->receiveMessage([
            'AttributeNames' => ['SentTimestamp'],
            'MaxNumberOfMessages' => 1,
            'MessageAttributeNames' => ['All'],
            'QueueUrl' => $this->queueUrl,
            'WaitTimeSeconds' => 0,
        ]);

        if (empty($result->get('Messages')[0])) {
            return null;
        }

        // See "Result Syntax" on: https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-sqs-2012-11-05.html#receivemessage
        $raw = $result->get('Messages')[0];
        $body = json_decode($raw['Body'], true);

        return SendToNotify::fromArray([
            'id' => $raw['ReceiptHandle'],
            'uuid' => $body['uuid'],
            'filename' => $body['filename'],
            'documentId' => $body['documentId'],
        ]);
    }

    public function delete(SendToNotify $command): void
    {
        // See https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-sqs-2012-11-05.html#deletemessage
        $this->client->deleteMessage([
            'QueueUrl' => $this->queueUrl,
            'ReceiptHandle' => $command->getId(),
        ]);
    }
}
