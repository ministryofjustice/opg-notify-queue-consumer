<?php

declare(strict_types=1);

namespace Opg\Command;

use InvalidArgumentException;

class UpdateDocumentStatus
{
    protected int $documentId;
    protected string $notifyId;
    protected string $notifyStatus;

    private function __construct()
    {
    }

    public static function fromArray(array $data): self
    {
        if (empty($data['documentId']) || !intval($data['documentId'])) {
            throw new InvalidArgumentException('Message doesn\'t contain a valid documentId');
        }

        if (empty($data['notifyId'])) {
            throw new InvalidArgumentException('Message doesn\'t contain a notifyId');
        }

        if (empty($data['notifyStatus'])) {
            throw new InvalidArgumentException('Message doesn\'t contain a notifyStatus');
        }

        $instance = new self();

        $instance->documentId = (int)$data['documentId'];
        $instance->notifyId = $data['notifyId'];
        $instance->notifyStatus = $data['notifyStatus'];

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
}
