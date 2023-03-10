<?php

declare(strict_types=1);

namespace NotifyQueueConsumerTest\Unit\Command\Model;

use NotifyQueueConsumer\Command\Model\AggregateValidationException;
use NotifyQueueConsumer\Command\Model\SendToNotify;
use PHPUnit\Framework\TestCase;

class SendToNotifyTest extends TestCase
{

    public static function fromArrayProvider (): array
    {
        return [
            ["HW"],
            ["PFA LAY"],
            ["PFA PRO"],
            ["PFA PA"],
            ["FINANCE"],
            [null]
        ];
    }
    /**
     * @dataProvider fromArrayProvider
     */
    public function testFromArraySuccess(?string $replyToType): void
    {
        $data = [
            'id' => '123',
            'uuid' => 'asd-456',
            'filename' => 'document.pdf',
            'documentId' => '1234',
            'recipientEmail' => 'test@test.com',
            'recipientName' => 'Test Test',
            'clientFirstName' => 'Sharilyn',
            'clientSurname' => 'Harrey',
            'sendBy' => [
                'method' => 'post',
                'documentType' => 'letter'
            ],
            'letterType' => 'a6',
            'pendingOrDueReportType' => 'OPG102',
            'caseNumber' => '74442574',
            'replyToType' => $replyToType
        ];

        $command = SendToNotify::fromArray($data);

        self::assertEquals($data['id'], $command->getId());
        self::assertEquals($data['uuid'], $command->getUuid());
        self::assertEquals($data['filename'], $command->getFilename());
        self::assertEquals($data['documentId'], $command->getDocumentId());
    }

    /**
     * @param array<string,string> $data
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
