<?php

declare(strict_types=1);

namespace Opg\Command;

use InvalidArgumentException;

class SendToNotify
{
    public string $id;
    public string $uuid;
    public string $filename;

    /**
     * @param array<string,string> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        if (empty($data['id'])) {
            throw new InvalidArgumentException('Message doesn\'t contain a id');
        }

        if (empty($data['uuid'])) {
            throw new InvalidArgumentException('Message doesn\'t contain a uuid');
        }

        if (empty($data['filename'])) {
            throw new InvalidArgumentException('Message doesn\'t contain a filename');
        }

        $instance = new self();
        $instance->id = $data['id'];
        $instance->uuid = $data['uuid'];
        $instance->filename = $data['filename'];

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
}
