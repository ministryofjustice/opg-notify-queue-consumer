<?php

declare(strict_types=1);

namespace NotifyQueueConsumer\Command\Model;

class UpdateDocumentStatus
{
    protected int $documentId;
    protected string $notifyId;
    protected string $notifyStatus;
    protected string $sendByMethod;
    protected ?string $recipientEmailAddress;

    private function __construct()
    {
    }

    /**
     * @param array<string,mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        AggregateValidationException::clearInstance();

        if (empty($data['documentId']) || !is_numeric($data['documentId'])) {
            AggregateValidationException::addError('Data doesn\'t contain a numeric documentId');
        }

        if (empty($data['notifyId'])) {
            AggregateValidationException::addError('Data doesn\'t contain a notifyId');
        }

        if (empty($data['notifyStatus'])) {
            AggregateValidationException::addError('Data doesn\'t contain a notifyStatus');
        }

        if (empty($data['sendByMethod'])) {
            AggregateValidationException::addError('Data doesn\'t contain a sendByMethod');
        }

        AggregateValidationException::checkAndThrow();

        $instance = new self();

        $instance->documentId = (int)$data['documentId'];
        $instance->notifyId = $data['notifyId'];
        $instance->notifyStatus = $data['notifyStatus'];
        $instance->sendByMethod = $data['sendByMethod'];
        $instance->recipientEmailAddress = $data['recipientEmailAddress'];

        return $instance;
    }

    public function getDocumentId(): int
    {
        return $this->documentId;
    }

    public function getNotifyId(): string
    {
        return $this->notifyId;
    }

    public function getNotifyStatus(): string
    {
        return $this->notifyStatus;
    }

    public function getSendByMethod(): string
    {
        return $this->sendByMethod;
    }

    public function getRecipientEmailAddress(): ?string
    {
        return $this->recipientEmailAddress;
    }
}
