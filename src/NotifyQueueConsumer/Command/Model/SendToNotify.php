<?php

declare(strict_types=1);

namespace NotifyQueueConsumer\Command\Model;

class SendToNotify
{
    protected string $id;
    protected string $uuid;
    protected string $filename;
    protected int $documentId;

    private function __construct()
    {
    }

    /**
     * @param array<string,string> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        AggregateValidationException::clearInstance();

        if (empty($data['id'])) {
            AggregateValidationException::addError('Data doesn\'t contain an id');
        }

        if (empty($data['uuid'])) {
            AggregateValidationException::addError('Data doesn\'t contain a uuid');
        }

        if (empty($data['filename'])) {
            AggregateValidationException::addError('Data doesn\'t contain a filename');
        }

        if (empty($data['documentId']) || !is_numeric($data['documentId'])) {
            AggregateValidationException::addError('Data doesn\'t contain a numeric documentId');
        }

        AggregateValidationException::checkAndThrow();

        $instance = new self();
        $instance->id = $data['id'];
        $instance->uuid = $data['uuid'];
        $instance->filename = $data['filename'];
        $instance->documentId = (int)$data['documentId'];

        return $instance;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function getDocumentId(): int
    {
        return $this->documentId;
    }
}
