<?php

declare(strict_types=1);

namespace NotifyQueueConsumer\Command\Model;

class SendToNotify
{
    protected string $id;
    protected string $uuid;
    protected string $filename;
    protected int $documentId;
    protected ?string $recipientName;
    protected ?string $recipientEmail;
    protected ?string $clientFirstName;
    protected ?string $clientSurname;
    protected ?string $pendingOrDueReportType;
    protected ?string $caseNumber;
    /**
     * @var array<string,string>
     */
    protected array $sendBy;
    protected ?string $letterType;

    private function __construct()
    {
    }

    /**
     * @param array<mixed> $data
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
        $instance->recipientName = $data['recipientName'];
        $instance->recipientEmail = $data['recipientEmail'];
        $instance->clientFirstName = $data['clientFirstName'];
        $instance->clientSurname = $data['clientSurname'];
        $instance->sendBy = $data['sendBy'];
        $instance->letterType = $data['letterType'];
        $instance->pendingOrDueReportType = $data['pendingOrDueReportType'];
        $instance->caseNumber = $data['caseNumber'];

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

    public function getRecipientName(): ?string
    {
        return $this->recipientName;
    }

    public function getRecipientEmail(): ?string
    {
        return $this->recipientEmail;
    }

    public function getClientFirstName(): ?string
    {
        return $this->clientFirstName;
    }

    public function getClientSurname(): ?string
    {
        return $this->clientSurname;
    }

    /**
     * @return array<string,string>
     */
    public function getSendBy(): array
    {
        return $this->sendBy;
    }

    public function getLetterType(): ?string
    {
        return $this->letterType;
    }

    public function getPendingOrDueReportType(): ?string
    {
        return $this->pendingOrDueReportType;
    }

    public function getCaseNumber(): ?string
    {
        return $this->caseNumber;
    }
}
