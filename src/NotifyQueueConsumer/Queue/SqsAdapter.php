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

        $message = $body['message'];

        return SendToNotify::fromArray([
            'id' => $raw['ReceiptHandle'],
            'uuid' => $message['uuid'],
            'filename' => $message['filename'],
            'documentId' => $message['documentId'],
            'recipientEmail' => $message['recipientEmail'],
            'recipientName' => $message['recipientName'],
            'sendBy' => $message['sendBy'],
            'clientFirstName' => $message['clientFirstName'],
            'clientSurname' => $message['clientSurname'],
            'letterType' => $message['letterType'],
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
            throw new UnexpectedValueException('Empty body');
        }

        if (empty($body['message'])) {
            throw new UnexpectedValueException('Empty message');
        }

        $message = $body['message'];

        $requiredFields = [
            'uuid',
            'filename',
            'documentId',
        ];

        if ($message['sendBy']['documentType'] === 'invoice') {
            $requiredFields = array_merge($requiredFields, [
                'recipientEmail',
                'recipientName',
                'sendBy',
                'letterType',
            ]);
        }

        foreach ($requiredFields as $requiredField) {
            if (empty($body['message'][$requiredField])) {
                throw new UnexpectedValueException('Missing "' . $requiredField . '"');
            }
        }
    }
}
