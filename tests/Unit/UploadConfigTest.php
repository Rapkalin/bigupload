<?php

namespace App\Tests\Unit;

use App\Constant\UploadConfig;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class UploadConfigTest extends TestCase
{
    #[DataProvider('fileNameProvider')]
    public function testIsAllowedExtension(string $fileName, bool $expected): void
    {
        self::assertSame($expected, UploadConfig::isAllowedExtension($fileName));
    }

    public static function fileNameProvider(): iterable
    {
        yield ['photo.jpg', true];
        yield ['PHOTO.JPEG', true];
        yield ['movie.final.MOV', true];
        yield ['subtitles.srt', true];
        yield ['archive.zip', true];
        yield ['photo.jpg.php', false];
        yield ['shell.phtml', false];
        yield ['README', false];
        yield ['.jpg.exe', false];
        yield ['', false];
    }

    #[DataProvider('validFileNameProvider')]
    public function testIsValidFileName(string $fileName, bool $expected): void
    {
        self::assertSame($expected, UploadConfig::isValidFileName($fileName));
    }

    public static function validFileNameProvider(): iterable
    {
        yield ['photo.jpg', true];
        yield ['My holidays (1) - été.jpg', true];
        yield ['日本.mp4', true];
        yield [str_repeat('a', 251) . '.jpg', true];
        yield [str_repeat('a', 252) . '.jpg', false];
        yield ['', false];
        yield ["a\0b.jpg", false];
        yield ["a\tb.jpg", false];
        yield ["a\x7fb.jpg", false];
        yield ["\xff.jpg", false];
    }

    public function testAllowedExtensionsAreTheFlattenedGroups(): void
    {
        self::assertSame(
            ['jpg', 'jpeg', 'png', 'gif', 'mp4', 'mov', 'pdf', 'mp3', 'vtt', 'srt', 'zip'],
            UploadConfig::allowedExtensions()
        );
    }

    public function testLimits(): void
    {
        self::assertSame(15 * 1024 ** 3, UploadConfig::MAX_FILE_SIZE);
        self::assertSame(250 * 1024 ** 2, UploadConfig::CHUNK_SIZE);
    }
}
