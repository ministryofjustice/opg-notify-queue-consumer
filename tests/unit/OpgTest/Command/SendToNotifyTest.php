<?php

declare(strict_types=1);

namespace OpgTest\Command;

use InvalidArgumentException;
use Opg\Command\SendToNotify;
use PHPUnit\Framework\TestCase;

class SendToNotifyTest extends TestCase
{
    public function testFromArraySuccess(): void
    {
        $data = [
            'id' => '123',
            'uuid' => 'asd-456',
            'filename' => 'document.pdf',
        ];

        $command = SendToNotify::fromArray($data);

        self::assertEquals($data['id'], $command->getId());
        self::assertEquals($data['uuid'], $command->getUuid());
        self::assertEquals($data['filename'], $command->getFilename());
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

        SendToNotify::fromArray($data);
    }

    /**
     * @return array<string,array<array<string,string>,string>>
     */
    public function commandDataProvider(): array
    {
        return [
            'missing id' => [['uuid' => 'asd-456', 'filename' => 'document.pdf'], 'Message doesn\'t contain an id'],
            'missing uuid' => [['id' => '123', 'filename' => 'document.pdf'], 'Message doesn\'t contain a uuid'],
            'missing filename' => [['id' => '123', 'uuid' => 'asd-456'], 'Message doesn\'t contain a filename'],
        ];
    }
}
