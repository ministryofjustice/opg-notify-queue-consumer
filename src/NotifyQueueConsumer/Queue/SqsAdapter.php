<?php

declare(strict_types=1);

namespace NotifyQueueConsumer\Queue;

use Aws\Sqs\SqsClient;
use NotifyQueueConsumer\Command\Model\SendToNotify;
use UnexpectedValueException;

class SqsAdapter implements QueueInterface
{
    private SqsClient $client;
    private string $queueUrl;
    private int $waitTime;

    public function __construct(SqsClient $client, string $queueUrl, int $waitTime)
    {
        $this->client = $client;
        $this->queueUrl = $queueUrl;
        $this->waitTime = $waitTime;
    }

    public function next(): ?SendToNotify
    {
        $result = $this->client->receiveMessage([
            'AttributeNames' => ['SentTimestamp'],
            'MaxNumberOfMessages' => 1,
            'MessageAttributeNames' => ['All'],
            'QueueUrl' => $this->queueUrl,
            'WaitTimeSeconds' => $this->waitTime,
        ]);

        if (empty($result->get('Messages')[0])) {
            return null;
        }

        // See "Result Syntax" on: https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-sqs-2012-11-05.html#receivemessage
        $raw = $result->get('Messages')[0];
        $body = json_decode($raw['Body'], true);

        $this->validateBody($body);

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

    /**
     * @param mixed $body
     */
    private function validateBody($body): void
    {
        if (empty($body)) {
            throw new UnexpectedValueException('Empty message');
        }

        if (empty($body['uuid'])) {
            throw new UnexpectedValueException('Missing "uuid"');
        }

        if (empty($body['filename'])) {
            throw new UnexpectedValueException('Missing "filename"');
        }

        if (empty($body['documentId'])) {
            throw new UnexpectedValueException('Missing "documentId"');
        }
    }
}
