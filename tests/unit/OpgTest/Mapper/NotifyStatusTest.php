<?php

declare(strict_types=1);

namespace OpgTest\Mapper;

use Opg\Mapper\NotifyStatus;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;

class NotifyStatusTest extends TestCase
{
    /**
     * @param string $notifyStatus
     * @param string $expectedResult
     * @dataProvider notifyStatusProvider
     */
    public function testToSiriusSuccess(string $notifyStatus, string $expectedResult)
    {
        $mapper = new NotifyStatus();
        $actualResult = $mapper->toSirius($notifyStatus);

        self::assertEquals($expectedResult, $actualResult);
    }

    /**
     * @return array<array<string>>
     */
    public function notifyStatusProvider(): array
    {
        return [
            ['Failed', 'rejected'],
            ['Virus scan failed', 'rejected'],
            ['Validation failed', 'rejected'],
            ['Pending virus check', 'queued'],
            ['Accepted', 'posting'],
            ['Received', 'posted'],
        ];
    }

    public function testToSiriusUnknownStatusFailure()
    {
        $mapper = new NotifyStatus();
        $unknownStatus = 'unforeseen consequence';

        self::expectException(UnexpectedValueException::class);
        self::expectExceptionMessage(sprintf('Unknown Notify status "%s"', $unknownStatus));

        $mapper->toSirius($unknownStatus);
    }
}
