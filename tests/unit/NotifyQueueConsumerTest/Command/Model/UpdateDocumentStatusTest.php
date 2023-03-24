<?php

declare(strict_types=1);

namespace NotifyQueueConsumerTest\Unit\Command\Model;

use NotifyQueueConsumer\Command\Model\AggregateValidationException;
use NotifyQueueConsumer\Command\Model\UpdateDocumentStatus;
use PHPUnit\Framework\TestCase;

class UpdateDocumentStatusTest extends TestCase
{
    public function testFromArraySuccessSupervision(): void
    {
        $data = [
            'notifyId' => '1',
            'notifyStatus' => 'accepted',
            'documentId' => '4545',
            'sendByMethod' => 'email',
            'recipientEmailAddress' => 'test@test.com'
        ];

        $command = UpdateDocumentStatus::fromArray($data);

        self::assertEquals($data['notifyId'], $command->getNotifyId());
        self::assertEquals($data['notifyStatus'], $command->getNotifyStatus());
        self::assertEquals($data['documentId'], $command->getDocumentId());
        self::assertEquals($data['sendByMethod'], $command->getSendByMethod());
        self::assertEquals($data['recipientEmailAddress'], $command->getRecipientEmailAddress());
    }

    public function testFromArraySuccessLpa(): void
    {
        $data = [
            'notifyId' => '1',
            'notifyStatus' => 'accepted',
            'documentId' => '4545',
            'sendByMethod' => 'post',
            'recipientEmailAddress' => null
        ];

        $command = UpdateDocumentStatus::fromArray($data);

        self::assertEquals($data['notifyId'], $command->getNotifyId());
        self::assertEquals($data['notifyStatus'], $command->getNotifyStatus());
        self::assertEquals($data['documentId'], $command->getDocumentId());
        self::assertEquals($data['sendByMethod'], $command->getSendByMethod());
        self::assertEquals($data['recipientEmailAddress'], $command->getRecipientEmailAddress());
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

        UpdateDocumentStatus::fromArray($data);
    }

    /**
     * @return array<string,array<array<string,string>,string>>
     */
    public static function commandDataProvider(): array
    {
        return [
            'missing notifyId' => [
                ['notifyStatus' => 'accepted', 'documentId' => '4545', 'sendByMethod' => 'email', 'recipientEmailAddress' => 'test@test.com'],
                'Data doesn\'t contain a notifyId'
            ],
            'missing notifyStatus' => [
                ['notifyId' => '1', 'documentId' => '4545', 'sendByMethod' => 'email', 'recipientEmailAddress' => 'test@test.com'],
                'Data doesn\'t contain a notifyStatus'
            ],
            'missing documentId' => [
                ['notifyId' => '1', 'notifyStatus' => 'accepted', 'sendByMethod' => 'email', 'recipientEmailAddress' => 'test@test.com'],
                'Data doesn\'t contain a numeric documentId'
            ],
            'missing sendByMethod' => [
                ['notifyId' => '1', 'notifyStatus' => 'accepted', 'documentId' => '4545', 'recipientEmailAddress' => 'test@test.com'],
                'Data doesn\'t contain a sendByMethod'
            ],
            'non-numeric documentId' => [
                ['notifyId' => '1', 'notifyStatus' => 'accepted', 'documentId' => 'word', 'recipientEmailAddress' => 'test@test.com'],
                'Data doesn\'t contain a numeric documentId'
            ],
            'missing mandatory' => [
                [],
                implode(', ', [
                        'Data doesn\'t contain a numeric documentId',
                        'Data doesn\'t contain a notifyId',
                        'Data doesn\'t contain a notifyStatus',
                        'Data doesn\'t contain a sendByMethod'
                    ]
                ),
            ],
        ];
    }
}
