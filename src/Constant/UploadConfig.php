<?php

namespace App\Constant;

/**
 * Single source of truth for the upload rules,
 * shared by the back-end, the purge command, the templates and upload.js (through data attributes).
 */
final class UploadConfig
{
    public const int CHUNK_SIZE = 250 * 1024 * 1024;
    public const int MAX_FILE_SIZE_GB = 15;
    public const int MAX_FILE_SIZE = self::MAX_FILE_SIZE_GB * 1024 * 1024 * 1024;
    public const int EXPIRATION_DAYS = 7;
    // A partial upload untouched for this long is considered abandoned
    public const int TEMP_FILE_MAX_AGE = 5 * 3600;
    // New uploads allowed per client IP and per hour
    public const int UPLOADS_PER_HOUR = 20;
    public const string SHOW_ID_PREFIX = 'bgpld-';
    public const string ZIP_EXTENSION = 'zip';
    public const string PART_EXTENSION = 'part';

    /** Extensions grouped by plupload filter title */
    public const array ALLOWED_EXTENSIONS = [
        'Image files' => ['jpg', 'jpeg', 'png', 'gif'],
        'Video files' => ['mp4', 'mov'],
        'Pdf files' => ['pdf'],
        'Audio files' => ['mp3'],
        'Subtitles' => ['vtt', 'srt'],
        'Zip files' => ['zip'],
    ];

    /**
     * @return list<string>
     */
    public static function allowedExtensions(): array
    {
        return array_merge(...array_values(self::ALLOWED_EXTENSIONS));
    }

    public static function isAllowedExtension(string $fileName): bool
    {
        return in_array(strtolower(pathinfo($fileName, PATHINFO_EXTENSION)), self::allowedExtensions(), true);
    }
}
