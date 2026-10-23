<?php

namespace App\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class FormatBytesTest extends TestCase
{
    #[DataProvider('sizeProvider')]
    public function testFormatBytes(int $bytes, string $expected): void
    {
        self::assertSame($expected, formatBytes($bytes));
    }

    public static function sizeProvider(): iterable
    {
        yield [0, '0.00B'];
        yield [512, '512.00B'];
        yield [1024, '1.00KB'];
        yield [512361, '500.35KB'];
        yield [250 * 1024 ** 2, '250.00MB'];
        yield [15 * 1024 ** 3, '15.00GB'];
        yield [2 * 1024 ** 4, '2.00TB'];
        yield [3 * 1024 ** 5, '3072.00TB'];
    }
}
