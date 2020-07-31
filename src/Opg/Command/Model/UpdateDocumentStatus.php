<?php

declare(strict_types=1);

namespace Opg\Command\Model;

use InvalidArgumentException;

class UpdateDocumentStatus
{
    protected int $documentId;
    protected string $notifyId;
    protected string $notifyStatus;

    private function __construct()
    {
    }

    /**
     * @param array<string,mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        if (empty($data['documentId']) || !is_numeric($data['documentId'])) {
            throw new InvalidArgumentException('Data doesn\'t contain a numeric documentId');
        }

        if (empty($data['notifyId'])) {
            throw new InvalidArgumentException('Data doesn\'t contain a notifyId');
        }

        if (empty($data['notifyStatus'])) {
            throw new InvalidArgumentException('Data doesn\'t contain a notifyStatus');
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
