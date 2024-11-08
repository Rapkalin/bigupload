<?php

namespace App\Services;

use AllowDynamicProperties;
use App\Kernel;
use Symfony\Component\HttpKernel\KernelInterface;

#[AllowDynamicProperties] final class FileService
{
    public function __construct(KernelInterface $kernel)
    {
        $this->uploadPath = $kernel->getProjectDir() . '/public/';
    }

    /**
     * @param $filename
     * @return string|null Retrieve the captured extension or null if no extension has been found
     */
    public function getFileExtension(string $filename): ?string
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
        return filesize($this->uploadPath . $filepath);
    }

    /**
     * @param string $filepath
     * @return string|null Retrieve the captured extension or null if no extension has been found
     * @throws \DateMalformedStringException
     */
    public function getFileCreatedAt(string $filepath): ?string
    {
        $created_at = date('m/d/Y H:i:s', filectime($filepath));
        return (new \DateTimeImmutable($created_at))->format('Y-m-d H:i:s');
    }

    /**
     * @param string $filepath
     * @param string $created_at
     * @return string|null Retrieve the captured extension or null if no extension has been found
     */
    public function getFileSizeExpirationDate(string $filepath, string $created_at): ?string
    {
        // Todo: calcul expiration date
        return  $created_at;
    }
}