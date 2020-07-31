<?php

declare(strict_types=1);

namespace OpgTest\Command\Model;

use InvalidArgumentException;
use Opg\Command\Model\UpdateDocumentStatus;
use PHPUnit\Framework\TestCase;

class UpdateDocumentStatusTest extends TestCase
{
    public function testFromArraySuccess(): void
    {
        $data = [
            'notifyId' => '1',
            'notifyStatus' => 'accepted',
            'documentId' => '4545',
        ];

        $command = UpdateDocumentStatus::fromArray($data);

        self::assertEquals($data['notifyId'], $command->getNotifyId());
        self::assertEquals($data['notifyStatus'], $command->getNotifyStatus());
        self::assertEquals($data['documentId'], $command->getDocumentId());
    }

    /**
     * @param array<string,string> $data
     * @param string               $expectedMessage
     * @dataProvider commandDataProvider
     */
    public function testFromArrayThrowsExceptionFailure(array $data, string $expectedMessage): void
    {
        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage($expectedMessage);

        UpdateDocumentStatus::fromArray($data);
    }

    /**
     * @return array<string,array<array<string,string>,string>>
     */
    public function commandDataProvider(): array
    {
        return [
            'missing notifyId' => [
                ['notifyStatus' => 'accepted', 'documentId' => '4545'],
                'Data doesn\'t contain a notifyId'
            ],
            'missing notifyStatus' => [
                ['notifyId' => '1', 'documentId' => '4545'],
                'Data doesn\'t contain a notifyStatus'
            ],
            'missing documentId' => [
                ['notifyId' => '1', 'notifyStatus' => 'accepted'],
                'Data doesn\'t contain a numeric documentId'
            ],
            'non-numeric documentId' => [
                ['notifyId' => '1', 'notifyStatus' => 'accepted', 'documentId' => 'word'],
                'Data doesn\'t contain a numeric documentId'
            ],
        ];
    }
}
