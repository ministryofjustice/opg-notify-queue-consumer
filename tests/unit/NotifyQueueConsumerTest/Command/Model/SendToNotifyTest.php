<?php

declare(strict_types=1);

namespace NotifyQueueConsumerTest\Command\Model;

use NotifyQueueConsumer\Command\Model\AggregateValidationException;
use NotifyQueueConsumer\Command\Model\SendToNotify;
use PHPUnit\Framework\TestCase;

class SendToNotifyTest extends TestCase
{
    public function testFromArraySuccess(): void
    {
        $data = [
            'id' => '123',
            'uuid' => 'asd-456',
            'filename' => 'document.pdf',
            'documentId' => '1234',
        ];

        $command = SendToNotify::fromArray($data);

        self::assertEquals($data['id'], $command->getId());
        self::assertEquals($data['uuid'], $command->getUuid());
        self::assertEquals($data['filename'], $command->getFilename());
        self::assertEquals($data['documentId'], $command->getDocumentId());
    }

    /**
     * @param array<string,string> $data
     * @param string               $expectedMessage
     * @dataProvider commandDataProvider
     */
    public function testFromArrayThrowsExceptionFailure(array $data, string $expectedMessage): void
    {
        self::expectException(AggregateValidationException::class);
        self::expectExceptionMessage($expectedMessage);

        SendToNotify::fromArray($data);
    }

    /**
     * @return array<string,array<array<string,string>,string>>
     */
    public function commandDataProvider(): array
    {
        return [
            'missing id' => [
                ['uuid' => 'asd-456', 'filename' => 'document.pdf', 'documentId' => '1234'],
                'Data doesn\'t contain an id'
            ],
            'missing uuid' => [
                ['id' => '123', 'filename' => 'document.pdf', 'documentId' => '1234'],
                'Data doesn\'t contain a uuid'
            ],
            'missing filename' => [
                ['id' => '123', 'uuid' => 'asd-456', 'documentId' => '1234'],
                'Data doesn\'t contain a filename'
            ],
            'missing documentId' => [
                ['id' => '123', 'uuid' => 'asd-456', 'filename' => 'document.pdf'],
                'Data doesn\'t contain a numeric documentId'
            ],
            'non-numeric documentId' => [
                ['id' => '123', 'uuid' => 'asd-456', 'filename' => 'document.pdf', 'documentId' => 'word'],
                'Data doesn\'t contain a numeric documentId'
            ],
            'missing all' => [
                [],
                implode(', ', [
                    'Data doesn\'t contain an id',
                    'Data doesn\'t contain a uuid',
                    'Data doesn\'t contain a filename',
                    'Data doesn\'t contain a numeric documentId',
                ])
            ]
        ];
    }
}