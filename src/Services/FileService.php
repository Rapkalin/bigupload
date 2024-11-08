<?php

namespace App\Services;

final class FileService
{
    /**
     * @param $filename
     * @return string|null Retrieve the captured extension or null if no extension has been found
     */
    public function getFileExtension($filename): ?string
    {
        if (preg_match('/\.([a-zA-Z0-9]+)$/', $filename, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * @param string $filepath
     * @return string|null Retrieve the captured extension or null if no extension has been found
     */
    public function getFileSize(string $filepath): ?string
    {
        return filesize($filepath);
    }

    /**
     * @param string $filepath
     * @return string|null Retrieve the captured extension or null if no extension has been found
     * @throws \DateMalformedStringException
     */
    public function getFileCreatedAt(string $filepath): ?string
    {
        $created_at = filectime($filepath);
        return (new \DateTimeImmutable($created_at))->format('Y-m-d H:i:s');
    }

    /**
     * @param string $filepath
     * @param int $created_at
     * @return string|null Retrieve the captured extension or null if no extension has been found
     */
    public function getFileSizeExpirationDate(string $filepath, int $created_at): ?string
    {
        // Todo: calcul expiration date
        return filectime($filepath);
    }
}