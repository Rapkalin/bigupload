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
